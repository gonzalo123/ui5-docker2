## Working with SAPUI5 locally part 3. Adding more services

In the previous project we moved one project to docker. The idea was to move exactly the same funcionality (even without touching anything within the source code). Now we're going to add more services. Yes, I know, it looks like overenginering, but I want to build something with different services working together. Let start.

We're going to change a little bit our original project. Now our frontend will only have one button. This button will increment the number of clicks but we're going to persists this information in a PostgreSQL database. Also, instead of incrementing the counter in the backenck, our backend will emit one event to a RabbitMQ message broker. We'll have one worker service listening to this event and this worker will persists the information. The comunication between the worker and the frontend (to show the incremented value), will be via websockets.

With those premises we are goint to need:
* Frontend: ui5 application
* Backend: PHP/lumen aplication
* Worker: nodejs application that is listening to RabbitMQ event and serving the websocket server (using socket.io)
* Nginx server
* PosgreSQL database
* RabbitMQ message broker.

As the previous examples, our PHP backend will be server via Nginx and PHP-FPM.

Here we can see to docker-compose file to set up all the services

```yaml
version: '3.4'

services:
  nginx:
    image: gonzalo123.nginx
    restart: always
    ports:
    - "8080:80"
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-nginx
    volumes:
    - ./src/backend:/code/src
    - ./src/.docker/web/site.conf:/etc/nginx/conf.d/default.conf
    networks:
    - app-network
  api:
    image: gonzalo123.api
    restart: always
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-lumen-dev
    environment:
      XDEBUG_CONFIG: remote_host=${MY_IP}
    volumes:
    - ./src/backend:/code/src
    networks:
    - app-network
  ui5:
    image: gonzalo123.ui5
    ports:
    - "8000:8000"
    restart: always
    volumes:
    - ./src/frontend:/code/src
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-ui5
    networks:
    - app-network
  io:
    image: gonzalo123.io
    ports:
    - "9999:9999"
    restart: always
    volumes:
    - ./src/io:/code/src
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-io
    networks:
    - app-network
  pg:
    image: gonzalo123.pg
    restart: always
    ports:
    - "5432:5432"
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-pg
    environment:
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
      POSTGRES_USER: ${POSTGRES_USER}
      POSTGRES_DB: ${POSTGRES_DB}
      PGDATA: /var/lib/postgresql/data/pgdata
    networks:
    - app-network
  rabbit:
    image: rabbitmq:3-management
    container_name: gonzalo123.rabbit
    restart: always
    ports:
    - "15672:15672"
    - "5672:5672"
    environment:
      RABBITMQ_ERLANG_COOKIE:
      RABBITMQ_DEFAULT_VHOST: /
      RABBITMQ_DEFAULT_USER: ${RABBITMQ_DEFAULT_USER}
      RABBITMQ_DEFAULT_PASS: ${RABBITMQ_DEFAULT_PASS}
    networks:
    - app-network
networks:
  app-network:
    driver: bridge
```

We're goint to use the same docker files than in the previous post but we also need new ones for worker, database server and message queue:

Worker:
```yaml
FROM node:alpine

EXPOSE 8000

WORKDIR /code/src
COPY ./io .
RUN npm install
ENTRYPOINT ["npm", "run", "serve"]
```

The worker script is simple script that serves the socket.io server and emmits a websocket within every message to the RabbitMQ queue.
```js
var amqp = require('amqp'),
  httpServer = require('http').createServer(),
  io = require('socket.io')(httpServer, {
    origins: '*:*',
  }),
  pg = require('pg')
;

require('dotenv').config();
var pgClient = new pg.Client(process.env.DB_DSN);

rabbitMq = amqp.createConnection({
  host: process.env.RABBIT_HOST,
  port: process.env.RABBIT_PORT,
  login: process.env.RABBIT_USER,
  password: process.env.RABBIT_PASS,
});

var sql = 'SELECT clickCount FROM docker.clicks';

// Please don't do this. Use lazy connections
// I'm 'lazy' to do it in this POC :)
pgClient.connect(function(err) {
  io.on('connection', function() {
    pgClient.query(sql, function(err, result) {
      var count = result.rows[0]['clickcount'];
      io.emit('click', {count: count});
    });

  });

  rabbitMq.on('ready', function() {
    var queue = rabbitMq.queue('ui5');
    queue.bind('#');

    queue.subscribe(function(message) {
      pgClient.query(sql, function(err, result) {
        var count = parseInt(result.rows[0]['clickcount']);
        count = count + parseInt(message.data.toString('utf8'));
        pgClient.query('UPDATE docker.clicks SET clickCount = $1', [count],
          function(err) {
            io.emit('click', {count: count});
          });
      });
    });
  });
});

httpServer.listen(process.env.IO_PORT);
```

Database server:
```yaml
FROM postgres:9.6-alpine
COPY pg/init.sql /docker-entrypoint-initdb.d/
```

As we can see we're going to generate the database estructure in the first build
```sql
CREATE SCHEMA docker;

CREATE TABLE docker.clicks (
  clickCount numeric(8) NOT NULL
);

ALTER TABLE docker.clicks
  OWNER TO username;

INSERT INTO docker.clicks(clickCount) values (0);
```

With the RabbitMQ server we're going to use the official docker image so we don't need to create one Dockerfile

We also have changed a little bit our Nginx configuration. We want to use Nginx to serve backend and also socket.io server. That's because we don't want to expose different ports to the internet.

```yaml
server {
    listen 80;
    index index.php index.html;
    server_name localhost;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /code/src/www;

    location /socket.io/ {
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_pass "http://io:9999";
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass api:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}
```

To avoid cors issues we can also use SCP destination (the localneo proxy in this example), to serve socket.io also. So we need to:
* change our neo-app.json file
```json
"routes": [
    ...
    {
      "path": "/socket.io",
      "target": {
        "type": "destination",
        "name": "SOCKETIO"
      },
      "description": "SOCKETIO"
    }
  ],
``` 
* and destination.json file alos
```json
  "destinations": {
    "SOCKETIO": {
      "url": "http://nginx:80/socket.io/"
    },
    "BACKEND": {
      "url": "http://nginx:80",
      "auth": "superSecretUser:superSecretPassword"
    }
  }
``` 

And basically that's all. Here also we can use a "production" docker-copose file without exposing all ports and mapping the filesystem to our local machine (usefull when we're developing)

```yaml
version: '3.4'

services:
  nginx:
    image: gonzalo123.nginx
    restart: always
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-nginx
    networks:
    - app-network
  api:
    image: gonzalo123.api
    restart: always
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-lumen
    networks:
    - app-network
  ui5:
    image: gonzalo123.ui5
    ports:
    - "80:8000"
    restart: always
    volumes:
    - ./src/frontend:/code/src
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-ui5
    networks:
    - app-network
  io:
    image: gonzalo123.io
    restart: always
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-io
    networks:
    - app-network
  pg:
    image: gonzalo123.pg
    restart: always
    build:
      context: ./src
      dockerfile: .docker/Dockerfile-pg
    environment:
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
      POSTGRES_USER: ${POSTGRES_USER}
      POSTGRES_DB: ${POSTGRES_DB}
      PGDATA: /var/lib/postgresql/data/pgdata
    networks:
    - app-network
  rabbit:
    image: rabbitmq:3-management
    restart: always
    environment:
      RABBITMQ_ERLANG_COOKIE:
      RABBITMQ_DEFAULT_VHOST: /
      RABBITMQ_DEFAULT_USER: ${RABBITMQ_DEFAULT_USER}
      RABBITMQ_DEFAULT_PASS: ${RABBITMQ_DEFAULT_PASS}
    networks:
    - app-network
networks:
  app-network:
    driver: bridge
```

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

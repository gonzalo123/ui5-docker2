CREATE SCHEMA docker;

CREATE TABLE docker.clicks (
  clickCount numeric(8) NOT NULL
);

ALTER TABLE docker.clicks
  OWNER TO username;

INSERT INTO docker.clicks(clickCount) values (0);

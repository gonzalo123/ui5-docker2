<?php

namespace App\Providers;

use PDO;
use Doctrine\DBAL\DriverManager;
use Illuminate\Support\ServiceProvider;
use Doctrine\DBAL\Connection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;


class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $dbh = new PDO(getenv("DB_DSN"), getenv("DB_USER"), getenv("DB_PASS"));
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $dbal = DriverManager::getConnection(['pdo' => $dbh]);
        $this->app->instance(Connection::class, $dbal);

        $connection = new AMQPStreamConnection(getenv('RABBITMQ_HOST'), getenv('RABBITMQ_PORT'), getenv('RABBITMQ_USER'), getenv('RABBITMQ_PASS'));
        $this->app->instance(AMQPChannel::class, $connection->channel());
    }

    public function register()
    {
    }
}

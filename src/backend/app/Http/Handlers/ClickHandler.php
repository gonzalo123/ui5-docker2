<?php

namespace App\Http\Handlers;

use Doctrine\DBAL\Connection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class ClickHandler
{
    private $conn;
    private $channel;

    public function __construct(Connection $conn, AMQPChannel $channel)
    {
        $this->conn    = $conn;
        $this->channel = $channel;
    }

    public function __invoke()
    {
        $msg = new AMQPMessage(1);
        $this->channel->basic_publish($msg, '', 'ui5');

        return ['date' => (new \DateTime())->format('c')];
    }
}

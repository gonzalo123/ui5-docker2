<?php

namespace App\Http\Handlers;

use Doctrine\DBAL\Connection;

class HomeHandler
{
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function __invoke()
    {
        return [
            'date'  => (new \DateTime())->format('c'),
            'count' => (int)$this->conn->fetchColumn("SELECT clickCount FROM docker.clicks"),
        ];
    }
}

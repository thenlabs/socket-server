<?php

namespace ThenLabs\SocketServer\Event;

use ThenLabs\SocketServer\Connection;
use ThenLabs\SocketServer\SocketServer;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class ConnectionEvent extends SocketServerEvent
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(SocketServer $server, Connection $connection)
    {
        parent::__construct($server);

        $this->connection = $connection;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}

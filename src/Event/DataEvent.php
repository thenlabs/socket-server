<?php

namespace ThenLabs\SocketServer\Event;

use ThenLabs\SocketServer\Connection;
use ThenLabs\SocketServer\SocketServer;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class DataEvent extends ConnectionEvent
{
    /**
     * @var string
     */
    protected $data;

    public function __construct(SocketServer $server, Connection $connection, string $data)
    {
        parent::__construct($server, $connection);

        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}

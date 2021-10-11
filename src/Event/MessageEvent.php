<?php

namespace ThenLabs\SocketServer\Event;

use ThenLabs\SocketServer\Connection;
use ThenLabs\SocketServer\SocketServer;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class MessageEvent extends ConnectionEvent
{
    /**
     * @var string
     */
    protected $message;

    public function __construct(SocketServer $server, Connection $connection, string $message)
    {
        parent::__construct($server, $connection);

        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}

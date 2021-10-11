<?php

namespace ThenLabs\SocketServer\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ThenLabs\SocketServer\SocketServer;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class SocketServerEvent extends Event
{
    protected $server;

    public function __construct(SocketServer $server)
    {
        $this->server = $server;
    }

    public function getServer(): SocketServer
    {
        return $this->server;
    }
}

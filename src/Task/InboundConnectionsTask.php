<?php

namespace ThenLabs\SocketServer\Task;

use ThenLabs\SocketServer\Connection;
use ThenLabs\SocketServer\Event\ConnectionEvent;
use ThenLabs\SocketServer\SocketServer;
use ThenLabs\TaskLoop\AbstractTask;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class InboundConnectionsTask extends AbstractTask
{
    /**
     * @var SocketServer
     */
    protected $server;

    public function __construct(SocketServer $server)
    {
        $this->server = $server;
    }

    public function run(): void
    {
        $clientSocket = @stream_socket_accept(
            $this->server->getSocket(),
            $this->server->getConfig()['timeout']
        );

        if (! is_resource($clientSocket)) {
            return;
        }

        $this->server->log('new_connection', [
            '%HOST%' => stream_socket_get_name($clientSocket, false),
        ]);

        stream_set_blocking($clientSocket, false);

        $connection = new Connection($this->server, $clientSocket);

        $this->server->getLoop()->addTask(new ConnectionTask($connection));

        $this->server->getDispatcher()->dispatch(
            new ConnectionEvent($this->server, $connection)
        );
    }
}

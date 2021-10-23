<?php

namespace ThenLabs\SocketServer\Task;

use ThenLabs\SocketServer\Connection;
use ThenLabs\SocketServer\Event\DisconnectionEvent;
use ThenLabs\TaskLoop\AbstractTask;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class ConnectionTask extends AbstractTask
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function run(): void
    {
        $server = $this->connection->getServer();
        $dispatcher = $server->getDispatcher();
        $socket = $this->connection->getSocket();
        $meta = @stream_get_meta_data($socket);

        if (false === $meta || true === $meta['eof']) {
            $server->log('disconnection', ['%HOST%' => $this->connection->getSocketName()]);

            $dispatcher->dispatch(new DisconnectionEvent($server, $this->connection));
            $server->getLoop()->dropTask($this);
            return;
        }

        $server->readDataFromConnection($this->connection);
    }
}

<?php

namespace ThenLabs\SocketServer\Task;

use Error;
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

        $disconnect = function () use ($server, $dispatcher) {
            $server->log('disconnection', ['%HOST%' => $this->connection->getSocketName()]);

            $dispatcher->dispatch(new DisconnectionEvent($server, $this->connection));
            $server->getLoop()->dropTask($this);
        };

        if (is_resource($socket) && !feof($socket)) {
            try {
                $meta = @stream_get_meta_data($socket);

                if (false === $meta || true === $meta['eof']) {
                    $disconnect();
                    return;
                }

                $server->readDataFromConnection($this->connection);
            } catch (Error $e) {
                $disconnect();
            }
        } else {
            $disconnect();
        }
    }
}

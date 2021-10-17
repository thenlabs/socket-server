<?php
/**
 * What this program does is accept multiple connections and forward
 * each incoming message to the rest of the connections.
 */

require_once __DIR__.'/../../bootstrap.php';

use ThenLabs\SocketServer\Event\ConnectionEvent;
use ThenLabs\SocketServer\Event\DataEvent;
use ThenLabs\SocketServer\Event\DisconnectionEvent;
use ThenLabs\SocketServer\SocketServer;

class HubServer extends SocketServer
{
    protected $connections = [];

    public function onConnection(ConnectionEvent $event): void
    {
        foreach ($this->connections as $connection) {
            $connection->writeLine("New connection.");
        }

        $this->connections[] = $event->getConnection();
    }

    public function onData(DataEvent $event): void
    {
        $data = $event->getData();

        switch ($data) {
            case 'exit':
                $event->getConnection()->close();
                break;

            case 'stop':
                $event->getServer()->stop();
                break;

            default:
                foreach ($this->connections as $connection) {
                    if ($connection != $event->getConnection()) {
                        $connection->writeLine($data);
                    }
                }
                break;
        }
    }

    public function onDisconnection(DisconnectionEvent $event): void
    {
        foreach ($this->connections as $id => $connection) {
            if ($connection == $event->getConnection()) {
                unset($this->connections[$id]);
                break;
            }
        }
    }
}

$server = new HubServer([
    'socket' => $argv[1] ?? 'tcp://127.0.0.1:9000',
    'loop_delay' => 100000, // the default value is 1 but causes conflicts with xdebug.
]);

$server->start();

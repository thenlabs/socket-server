<?php
/**
 * What this program does is accept multiple connections and forward
 * each incoming message to the rest of the connections.
 */

require_once __DIR__.'/../../bootstrap.php';

use ThenLabs\SocketServer\Event\ConnectionEvent;
use ThenLabs\SocketServer\Event\DisconnectionEvent;
use ThenLabs\SocketServer\Event\MessageEvent;
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

    public function onMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();

        switch ($message) {
            case 'exit':
                $event->getConnection()->close();
                break;

            case 'stop':
                $event->getServer()->stop();
                break;

            default:
                foreach ($this->connections as $connection) {
                    if ($connection != $event->getConnection()) {
                        $connection->writeLine($message);
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

$server = new HubServer(['socket' => $argv[1] ?? 'tcp://127.0.0.1:9000']);
$server->start();

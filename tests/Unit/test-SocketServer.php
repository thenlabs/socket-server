<?php

use ThenLabs\SocketServer\Event\ConnectionEvent;
use ThenLabs\SocketServer\Exception\MissingUrlException;
use ThenLabs\SocketServer\SocketServer;
use ThenLabs\TaskLoop\TaskLoop;

test(function () {
    $this->expectException(MissingUrlException::class);

    new SocketServer();
});

test(function () {
    $config = [
        'socket' => 'tcp://127.0.0.1:9000',
        'default_listeners' => [
            'onConnection' => false,
        ],
    ];

    $server = new class($config) extends SocketServer {
        public function onConnection(): void
        {
        }
    };

    $this->assertFalse($server->getDispatcher()->hasListeners(ConnectionEvent::class));
});

test(function () {
    $config = [
        'socket' => 'tcp://127.0.0.1:9000',
        'default_listeners' => [
            'onMessage' => false,
        ],
    ];

    $server = new class($config) extends SocketServer {
        public function onMessage(): void
        {
        }
    };

    $this->assertFalse($server->getDispatcher()->hasListeners(MessageEvent::class));
});

test(function () {
    $config = [
        'socket' => 'tcp://127.0.0.1:9000',
        'default_listeners' => [
            'onDisconnection' => false,
        ],
    ];

    $server = new class($config) extends SocketServer {
        public function onDisconnection(): void
        {
        }
    };

    $this->assertFalse($server->getDispatcher()->hasListeners(DisconnectionEvent::class));
});

test(function () {
    $loop = $this->getMockBuilder(TaskLoop::class)
        ->disableOriginalConstructor()
        ->setMethods(['runTasks'])
        ->getMock();
    $loop->expects($this->once())
        ->method('runTasks')
    ;

    $server = new SocketServer(['socket' => 'tcp://127.0.0.1:9090']);
    $server->setLoop($loop);

    $server->run(); // act
});

<?php
declare(strict_types=1);

namespace ThenLabs\SocketServer;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use ThenLabs\SocketServer\Event\ConnectionEvent;
use ThenLabs\SocketServer\Event\DisconnectionEvent;
use ThenLabs\SocketServer\Event\MessageEvent;
use ThenLabs\SocketServer\Exception\SocketServerException;
use ThenLabs\SocketServer\Task\ConnectionTask;
use ThenLabs\SocketServer\Task\InboundConnectionsTask;
use ThenLabs\TaskLoop\TaskLoop;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class SocketServer
{
    /**
     * @var array
     */
    protected $defaultConfig = [
        'socket' => '',
        'blocking' => false,
        'logger_name' => 'thenlabs_socket_server',
        'timeout' => -1,
        'loop_delay' => 1,
        'log_messages' => [
            'server_started' => 'Server started in %SOCKET%',
            'server_stopped' => 'Server stopped.',
            'new_connection' => 'New connection from %HOST%',
            'disconnection'  => 'Connection from %HOST% has been closed.',
        ],
        'default_listeners' => [
            'onConnection'    => true,
            'onMessage'       => true,
            'onDisconnection' => true,
        ],
    ];

    /**
     * @var array
     */
    protected $config;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TaskLoop
     */
    protected $loop;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->defaultConfig, $config);

        if (! isset($config['socket'])) {
            throw new Exception\MissingUrlException();
        }

        $this->dispatcher = new EventDispatcher();

        $this->loop = new TaskLoop();
        $this->loop->addTask(new Task\InboundConnectionsTask($this));

        $this->logger = new Logger($this->config['logger_name']);
        $this->logger->pushHandler(new StreamHandler(STDOUT));

        if ($this->config['default_listeners']['onConnection']) {
            $onConnectionCallback = [$this, 'onConnection'];
            if (is_callable($onConnectionCallback)) {
                $this->dispatcher->addListener(ConnectionEvent::class, $onConnectionCallback);
            }
        }

        $onMessageCallback = [$this, 'onMessage'];
        if (is_callable($onMessageCallback)) {
            $this->dispatcher->addListener(MessageEvent::class, $onMessageCallback);
        }

        $onDisconnectionCallback = [$this, 'onDisconnection'];
        if (is_callable($onDisconnectionCallback)) {
            $this->dispatcher->addListener(DisconnectionEvent::class, $onDisconnectionCallback);
        }
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function getLoop(): TaskLoop
    {
        return $this->loop;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function getLogMessage(string $key, array $parameters = []): ?string
    {
        if (! isset($this->config['log_messages'][$key]) ||
            ! is_string($this->config['log_messages'][$key])
        ) {
            return null;
        }

        return str_replace(
            array_keys($parameters),
            array_values($parameters),
            $this->config['log_messages'][$key]
        );
    }

    protected function createSocket()
    {
        $socket = stream_socket_server(
            $this->config['socket'],
            $errorCode,
            $errorMessage
        );

        if (false === $this->socket) {
            throw new SocketServerException($errorMessage, $errorCode);
        }

        return $socket;
    }

    public function start(): void
    {
        $this->socket = $this->createSocket();

        $this->logger->debug(
            $this->getLogMessage('server_started', ['%SOCKET%' => $this->config['socket']])
        );

        stream_set_blocking($this->socket, false);

        $this->loop->addTask(new InboundConnectionsTask($this));
        $this->loop->start($this->config['loop_delay']);
    }

    public function stop(): void
    {
        fclose($this->socket);

        $this->logger->debug($this->getLogMessage('server_stopped'));

        $this->loop->stop();

        foreach ($this->loop->getTasks() as $task) {
            $this->loop->dropTask($task);

            if ($task instanceof ConnectionTask) {
                fclose($task->getConnection()->getSocket());
            }
        }
    }
}

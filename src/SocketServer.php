<?php
declare(strict_types=1);

namespace ThenLabs\SocketServer;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use ThenLabs\SocketServer\Event\ConnectionEvent;
use ThenLabs\SocketServer\Event\DataEvent;
use ThenLabs\SocketServer\Event\DisconnectionEvent;
use ThenLabs\SocketServer\Exception\SocketServerException;
use ThenLabs\SocketServer\Task\ConnectionTask;
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
        'timeout' => 0,
        'loop_delay' => 1,
        'log_messages' => [
            'server_started' => 'Server started in %SOCKET%',
            'server_stopped' => 'Server stopped.',
            'new_connection' => 'New connection from %HOST%',
            'disconnection'  => 'Connection from %HOST% has been closed.',
        ],
        'default_listeners' => [
            'onConnection'    => true,
            'onData'          => true,
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

        if (isset($this->config['default_listeners']['onConnection']) &&
            true === $this->config['default_listeners']['onConnection']
        ) {
            $onConnectionCallback = [$this, 'onConnection'];
            if (is_callable($onConnectionCallback)) {
                $this->dispatcher->addListener(ConnectionEvent::class, $onConnectionCallback);
            }
        }

        if (isset($this->config['default_listeners']['onData']) &&
            true === $this->config['default_listeners']['onData']
        ) {
            $onMessageCallback = [$this, 'onData'];
            if (is_callable($onMessageCallback)) {
                $this->dispatcher->addListener(DataEvent::class, $onMessageCallback);
            }
        }

        if (isset($this->config['default_listeners']['onDisconnection']) &&
            true === $this->config['default_listeners']['onDisconnection']
        ) {
            $onDisconnectionCallback = [$this, 'onDisconnection'];
            if (is_callable($onDisconnectionCallback)) {
                $this->dispatcher->addListener(DisconnectionEvent::class, $onDisconnectionCallback);
            }
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

    public function setLoop(TaskLoop $loop): void
    {
        $this->loop = $loop;
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

    public function log(string $key, array $parameters = [], int $level = Logger::DEBUG): void
    {
        $message = $this->getLogMessage($key, $parameters);

        if (null === $message) {
            return;
        }

        $this->logger->addRecord($level, $message);
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

    public function run(): void
    {
        $this->loop->runTasks();
    }

    public function start(bool $startLoop = true): void
    {
        $this->socket = $this->createSocket();

        $this->log('server_started', ['%SOCKET%' => $this->config['socket']]);

        stream_set_blocking($this->socket, false);

        if ($startLoop) {
            $this->loop->start($this->config['loop_delay']);
        }
    }

    public function stop(): void
    {
        fclose($this->socket);

        $this->log('server_stopped');

        $this->loop->stop();

        foreach ($this->loop->getTasks() as $task) {
            $this->loop->dropTask($task);

            if ($task instanceof ConnectionTask) {
                fclose($task->getConnection()->getSocket());
            }
        }
    }

    /**
     * Read input data from a client socket.
     *
     * With this method it's implements the strategy for read the input data from clients. Can be
     * seen that by default the sockets are read using the fgets function. In case which will be
     * necessary to implement a custom strategy, it's should override this method.
     *
     * Keep in mind that this method is the responsible to trigger the event.
     *
     * @param Connection $connection
     * @return void
     */
    public function readDataFromConnection(Connection $connection): void
    {
        $data = stream_get_contents($connection->getSocket());

        if (is_string($data) && ! empty($data)) {
            $this->dispatcher->dispatch(new DataEvent($this, $connection, $data));
        }
    }
}

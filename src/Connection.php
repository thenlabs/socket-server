<?php

namespace ThenLabs\SocketServer;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class Connection
{
    /**
     * @var SocketServer
     */
    protected $server;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var string
     */
    protected $socketName;

    public function __construct(SocketServer $server, $socket)
    {
        $this->server = $server;
        $this->socket = $socket;
        $this->socketName = stream_socket_get_name($socket, false);
    }

    public function getServer(): SocketServer
    {
        return $this->server;
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function getSocketName(): string
    {
        return $this->socketName;
    }

    public function write(string $message)
    {
        return fwrite($this->socket, $message, strlen($message));
    }

    public function writeLine(string $message)
    {
        if ($message[-1] !== PHP_EOL) {
            $message .= PHP_EOL;
        }

        return $this->write($message);
    }

    public function read(int $length)
    {
        return fread($this->socket, $length);
    }

    public function readLine(?int $length = null)
    {
        return fgets($this->socket, $length);
    }

    public function close(): void
    {
        fclose($this->socket);
    }
}

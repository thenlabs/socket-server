<?php

use ThenLabs\SocketServer\Exception\MissingUrlException;
use ThenLabs\SocketServer\SocketServer;

test(function () {
    $this->expectException(MissingUrlException::class);

    new SocketServer();
});

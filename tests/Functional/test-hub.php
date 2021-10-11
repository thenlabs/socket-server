<?php

use PHPUnit\Framework\Assert;
use Symfony\Component\Process\Process;

testCase(function () {
    staticProperty('serverProcess');

    setUpBeforeClassOnce(function () {
        // empty the logs file.
        file_put_contents(LOGS_FILE, '');

        static::$serverProcess = new Process(['php', 'tests/Functional/hub.php', $_ENV['SOCKET_URL']]);
        static::$serverProcess->start();

        sleep(1);
    });

    testCase(function () {
        staticProperty('client1');
        staticProperty('client2');
        staticProperty('client3');
        staticProperty('message2');

        setUpBeforeClassOnce(function () {
            static::$client1 = stream_socket_client($_ENV['SOCKET_URL']);
            static::$client2 = stream_socket_client($_ENV['SOCKET_URL']);
            static::$client3 = stream_socket_client($_ENV['SOCKET_URL']);

            sleep(1);

            Assert::assertEquals("New connection.\n", fgets(static::$client1));
            Assert::assertEquals("New connection.\n", fgets(static::$client1));
            Assert::assertEquals("New connection.\n", fgets(static::$client2));

            static::$message2 = uniqid('message').PHP_EOL;

            fwrite(static::$client2, static::$message2, strlen(static::$message2));
        });

        test(function () {
            $this->assertEquals(static::$message2, fgets(static::$client1));
        });

        test(function () {
            $this->assertEquals(static::$message2, fgets(static::$client3));
        });

        tearDownAfterClassOnce(function () {
            static::$serverProcess->stop();
        });
    });
});

<?php

namespace Middlewares\Tests;

use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Middlewares\Writer;
use PHPUnit\Framework\TestCase;

class WriterTest extends TestCase
{
    public function testIndex()
    {
        $file = __DIR__.'/assets/tmp/index.html';

        self::rm($file);

        $response = Dispatcher::run(
            [
                Writer::createFromDirectory(__DIR__.'/assets'),
                function () {
                    echo 'Hello world';
                },
            ],
            Factory::createServerRequest([], 'GET', '/tmp')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(is_file($file));
        $this->assertEquals(file_get_contents($file), (string) $response->getBody());

        self::rm($file);
    }

    public function testGzIndex()
    {
        $file = __DIR__.'/assets/tmp/index.html.gz';

        self::rm($file);

        $response = Dispatcher::run(
            [
                Writer::createFromDirectory(__DIR__.'/assets'),
                function () {
                    echo gzencode('Hello world');

                    return Factory::createResponse()
                        ->withHeader('Content-Encoding', 'gzip');
                },
            ],
            Factory::createServerRequest([], 'GET', '/tmp')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(is_file($file));
        $this->assertEquals(file_get_contents($file), (string) $response->getBody());

        self::rm($file);
    }

    public function testPost()
    {
        $file = __DIR__.'/assets/tmp/index.html.gz';

        self::rm($file);

        $response = Dispatcher::run(
            [
                Writer::createFromDirectory(__DIR__.'/assets'),
                function () {
                    echo 'Hello world';
                },
            ],
            Factory::createServerRequest([], 'POST', '/tmp')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(is_file($file));

        self::rm($file);
    }

    public function testInvalidStatusCode()
    {
        $file = __DIR__.'/assets/tmp/index.html.gz';

        self::rm($file);

        $response = Dispatcher::run(
            [
                Writer::createFromDirectory(__DIR__.'/assets'),
                function () {
                    return Factory::createResponse(500);
                },
            ],
            Factory::createServerRequest([], 'GET', '/tmp')
        );

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertFalse(is_file($file));

        self::rm($file);
    }

    public function testNoCacheHeaders()
    {
        $file = __DIR__.'/assets/tmp/index.html.gz';

        self::rm($file);

        $response = Dispatcher::run(
            [
                Writer::createFromDirectory(__DIR__.'/assets'),
                function () {
                    echo 'Hello world';

                    return Factory::createResponse()
                        ->withHeader('Cache-Control', 'no-cache');
                },
            ],
            Factory::createServerRequest([], 'GET', '/tmp')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(is_file($file));

        self::rm($file);
    }

    private static function rm($file)
    {
        if (is_file($file)) {
            unlink($file);
        }

        if (is_dir(dirname($file))) {
            rmdir(dirname($file));
        }
    }
}

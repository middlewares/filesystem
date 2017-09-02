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

        $request = Factory::createServerRequest([], 'GET', '/tmp');

        $response = Dispatcher::run([
            new Writer(__DIR__.'/assets'),
            function () {
                echo 'Hello world';
            },
        ], $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(is_file($file));
        $this->assertEquals(file_get_contents($file), (string) $response->getBody());

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

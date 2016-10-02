<?php

namespace Middlewares\Tests;

use Middlewares\Writer;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use mindplay\middleman\Dispatcher;

class WriterTest extends \PHPUnit_Framework_TestCase
{
    public function testIndex()
    {
        $file = __DIR__.'/assets/tmp/index.html';

        self::rm($file);

        $request = new Request('/tmp', 'GET');

        $response = (new Dispatcher([
            new Writer(__DIR__.'/assets'),
            function () {
                $response = new Response();
                $response->getBody()->write('Hello world');

                return $response;
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
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

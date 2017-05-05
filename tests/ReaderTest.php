<?php

namespace Middlewares\Tests;

use Middlewares\Reader;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class ReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidMethod()
    {
        $request = Factory::createServerRequest([], 'POST', '/image.png');

        $response = Dispatcher::run([
            new Reader(__DIR__.'/assets'),
        ], $request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('GET', $response->getHeaderLine('Allow'));
    }

    public function testNotFound()
    {
        $request = Factory::createServerRequest([], 'GET', '/not-found');

        $response = Dispatcher::run([
            new Reader(__DIR__.'/assets'),
        ], $request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testContinueOnError()
    {
        $request = Factory::createServerRequest([], 'GET', '/not-found');

        $response = Dispatcher::run([
            (new Reader(__DIR__.'/assets'))->continueOnError(),
            function () {
                echo 'Fallback';
            },
        ], $request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Fallback', (string) $response->getBody());
    }

    public function testIndex()
    {
        $request = Factory::createServerRequest([], 'GET', '/hello-world');

        $response = Dispatcher::run([
            new Reader(__DIR__.'/assets'),
        ], $request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = file_get_contents(__DIR__.'/assets/hello-world/index.html');
        $this->assertEquals($content, (string) $response->getBody());
    }

    public function testContentRange()
    {
        $request = Factory::createServerRequest([], 'GET', '/image.png')
            ->withHeader('Range', 'bytes=300-');

        $response = Dispatcher::run([
            new Reader(__DIR__.'/assets'),
        ], $request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(206, $response->getStatusCode());
        $this->assertRegexp('|^bytes 300-\d{6}/\d{6}$|', $response->getHeaderLine('Content-Range'));
    }
}

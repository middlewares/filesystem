<?php

namespace Middlewares\Tests;

use Middlewares\Reader;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\CallableMiddleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;

class ReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidMethod()
    {
        $request = new ServerRequest([], [], '/image.png', 'POST');

        $response = (new Dispatcher([
            new Reader(__DIR__.'/assets'),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testNotFound()
    {
        $request = new ServerRequest([], [], '/not-found', 'GET');

        $response = (new Dispatcher([
            new Reader(__DIR__.'/assets'),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testContinueOnError()
    {
        $request = new ServerRequest([], [], '/not-found', 'GET');

        $response = (new Dispatcher([
            (new Reader(__DIR__.'/assets'))->continueOnError(),
            new CallableMiddleware(function () {
                $response = new Response();
                $response->getBody()->write('Fallback');

                return $response;
            }),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Fallback', (string) $response->getBody());
    }

    public function testIndex()
    {
        $request = new ServerRequest([], [], '/hello-world', 'GET');

        $response = (new Dispatcher([
            new Reader(__DIR__.'/assets'),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());

        $content = file_get_contents(__DIR__.'/assets/hello-world/index.html');
        $this->assertEquals($content, (string) $response->getBody());
    }

    public function testContentRange()
    {
        $request = (new ServerRequest([], [], '/image.png', 'GET'))
            ->withHeader('Range', 'bytes=300-');

        $response = (new Dispatcher([
            new Reader(__DIR__.'/assets'),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(206, $response->getStatusCode());
        $this->assertRegexp('|^bytes 300-\d{6}/\d{6}$|', $response->getHeaderLine('Content-Range'));
    }
}

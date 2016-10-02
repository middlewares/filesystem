<?php

namespace Middlewares\Tests;

use Middlewares\Reader;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use mindplay\middleman\Dispatcher;

class ReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidMethod()
    {
        $request = new Request('/image.png', 'POST');

        $response = (new Dispatcher([
            new Reader(__DIR__.'/assets'),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testNotFound()
    {
        $request = new Request('/not-found', 'GET');

        $response = (new Dispatcher([
            new Reader(__DIR__.'/assets'),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testContinueOnError()
    {
        $request = new Request('/not-found', 'GET');

        $response = (new Dispatcher([
            (new Reader(__DIR__.'/assets'))->continueOnError(),
            function () {
                $response = new Response();
                $response->getBody()->write('Fallback');

                return $response;
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Fallback', (string) $response->getBody());
    }

    public function testIndex()
    {
        $request = new Request('/hello-world', 'GET');

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
        $request = (new Request('/image.png', 'GET'))
            ->withHeader('Range', 'bytes=300-');

        $response = (new Dispatcher([
            new Reader(__DIR__.'/assets'),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(206, $response->getStatusCode());
        $this->assertRegexp('|^bytes 300-\d{6}/\d{6}$|', $response->getHeaderLine('Content-Range'));
    }
}

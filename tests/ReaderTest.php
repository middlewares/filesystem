<?php

namespace Middlewares\Tests;

use Middlewares\Reader;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    public function testInvalidMethod()
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest([], 'POST', '/image.png')
        );

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('GET', $response->getHeaderLine('Allow'));
    }

    public function testGz()
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest([], 'GET', '/image2.png')
                ->withHeader('Accept-Encoding', 'gzip')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('gzip', $response->getHeaderLine('Content-Encoding'));
    }

    public function testNotFound()
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest([], 'GET', '/not-found')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testContinueOnNotFound()
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets')
                    ->continueOnError(),

                function () {
                    echo 'Fallback';
                },
            ],
            Factory::createServerRequest([], 'GET', '/not-found')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Fallback', (string) $response->getBody());
    }

    public function testContinueOnInvalidMethod()
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets')
                    ->continueOnError(),

                function () {
                    echo 'Fallback';
                },
            ],
            Factory::createServerRequest([], 'POST', '/image.png')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Fallback', (string) $response->getBody());
    }

    public function testIndex()
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest([], 'GET', '/hello-world')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $content = file_get_contents(__DIR__.'/assets/hello-world/index.html');
        $this->assertEquals($content, (string) $response->getBody());
    }

    public function testContentRange()
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest([], 'GET', '/image.png')
                ->withHeader('Range', 'bytes=300-')
        );

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertRegexp('|^bytes 300-\d{6}/\d{6}$|', $response->getHeaderLine('Content-Range'));
    }

    public function testInvalidContentRange()
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest([], 'GET', '/image.png')
                ->withHeader('Range', 'xx=300-')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}

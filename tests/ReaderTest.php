<?php

declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\Reader;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    /**
     * phpunit 8 support
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(parent::class, 'assertMatchesRegularExpression')) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);

            return;
        }

        self::assertRegExp($pattern, $string, $message);
    }

    public function testInvalidMethod(): void
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest('POST', '/image.png')
        );

        self::assertEquals(405, $response->getStatusCode());
        self::assertEquals('GET', $response->getHeaderLine('Allow'));
    }

    public function testGz(): void
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest('GET', '/image2.png')
                ->withHeader('Accept-Encoding', 'gzip')
        );

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('gzip', $response->getHeaderLine('Content-Encoding'));
    }

    public function testNotFound(): void
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest('GET', '/not-found')
        );

        self::assertEquals(404, $response->getStatusCode());
    }

    public function testContinueOnNotFound(): void
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets')
                    ->continueOnError(),

                function () {
                    echo 'Fallback';
                },
            ],
            Factory::createServerRequest('GET', '/not-found')
        );

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Fallback', (string) $response->getBody());
    }

    public function testContinueOnInvalidMethod(): void
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets')
                    ->continueOnError(),

                function () {
                    echo 'Fallback';
                },
            ],
            Factory::createServerRequest('POST', '/image.png')
        );

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Fallback', (string) $response->getBody());
    }

    public function testIndex(): void
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest('GET', '/hello-world')
        );

        self::assertEquals(200, $response->getStatusCode());

        $content = file_get_contents(__DIR__.'/assets/hello-world/index.html');
        self::assertEquals($content, (string) $response->getBody());
    }

    public function testContentRange(): void
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest('GET', '/image.png')
                ->withHeader('Range', 'bytes=300-')
        );

        self::assertEquals(206, $response->getStatusCode());
        self::assertMatchesRegularExpression('|^bytes 300-\d{6}/\d{6}$|', $response->getHeaderLine('Content-Range'));
    }

    public function testInvalidContentRange(): void
    {
        $response = Dispatcher::run(
            [
                Reader::createFromDirectory(__DIR__.'/assets'),
            ],
            Factory::createServerRequest('GET', '/image.png')
                ->withHeader('Range', 'xx=300-')
        );

        self::assertEquals(200, $response->getStatusCode());
    }
}

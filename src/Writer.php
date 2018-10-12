<?php
declare(strict_types = 1);

namespace Middlewares;

use Middlewares\Utils\Traits\HasStreamFactory;
use Middlewares\Utils\Factory;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use League\Flysystem\FilesystemInterface;
use RuntimeException;

class Writer extends Filesystem implements MiddlewareInterface
{
    use HasStreamFactory;

    protected $filesystem;

    public static function createFromDirectory(
        string $path,
        StreamFactoryInterface $streamFactory = null
    ): self {
        return new static(static::createLocalFlysystem($path), $streamFactory);
    }

    public function __construct(
        FilesystemInterface $filesystem,
        StreamFactoryInterface $streamFactory = null
    ) {
        $this->filesystem = $filesystem;
        $this->streamFactory = $streamFactory ?: Factory::getStreamFactory();
    }

    /**
     * Process a request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($this->isWritable($request, $response)) {
            $path = static::getFilename($request->getUri()->getPath());

            //if it's gz compressed, append .gz
            if (strtolower($response->getHeaderLine('Content-Encoding')) === 'gzip') {
                $path .= '.gz';
            }

            $resource = $response->getBody()->detach();

            if ($resource === null) {
                throw new RuntimeException('Error on detach the stream body'); //@codeCoverageIgnore
            }

            $this->filesystem->writeStream($path, $resource);

            return $response->withBody($this->createStreamFromResource($resource));
        }

        return $response;
    }

    /**
     * Check whether the response is writable or not.
     */
    private function isWritable(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        if ($request->getMethod() !== 'GET') {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $cacheControl = $response->getHeaderLine('Cache-Control');

        if (stripos($cacheControl, 'no-cache') !== false || stripos($cacheControl, 'no-store') !== false) {
            return false;
        }

        return true;
    }
}

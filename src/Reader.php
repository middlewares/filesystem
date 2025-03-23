<?php
declare(strict_types = 1);

namespace Middlewares;

use League\Flysystem\FilesystemOperator;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class Reader extends Filesystem implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var bool
     */
    private $continueOnError = false;

    public static function createFromDirectory(
        string $path,
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ): self {
        /* @note We use static so that other classes can extend it and get the expected behaviour */
        return new static(static::createLocalFlysystem($path), $responseFactory, $streamFactory);
    }

    public function __construct(
        FilesystemOperator $filesystem,
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->filesystem = $filesystem;
        $this->responseFactory = $responseFactory ?: Factory::getResponseFactory();
        $this->streamFactory = $streamFactory ?: Factory::getStreamFactory();
    }

    /**
     * Configure if continue to the next middleware if the file is not found.
     */
    public function continueOnError(bool $continueOnError = true): self
    {
        $this->continueOnError = $continueOnError;

        return $this;
    }

    /**
     * Process a request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //Only GET methods are allowed
        if ($request->getMethod() !== 'GET') {
            if ($this->continueOnError) {
                return $handler->handle($request);
            }

            return $this->responseFactory->createResponse(405)->withHeader('Allow', 'GET');
        }

        $file = static::getFilename($request->getUri()->getPath());

        if ($this->filesystem->fileExists($file)) {
            return $this->read($request, $file);
        }

        //If the file does not exists, check if is gzipped
        $file .= '.gz';

        if (stripos($request->getHeaderLine('Accept-Encoding'), 'gzip') === false
            || !$this->filesystem->fileExists($file)
        ) {
            if ($this->continueOnError) {
                return $handler->handle($request);
            }

            return $this->responseFactory->createResponse(404);
        }

        return $this->read($request, $file)->withHeader('Content-Encoding', 'gzip');
    }

    /**
     * Read a file and returns a stream.
     */
    private function read(ServerRequestInterface $request, string $file): ResponseInterface
    {
        /** @var resource|false $resource */
        $resource = $this->filesystem->readStream($file);

        if ($resource === false) {
            throw new RuntimeException(sprintf('Unable to read the file "%s"', $file)); //@codeCoverageIgnore
        }

        $stream = $this->streamFactory->createStreamFromResource($resource);
        $response = $this->responseFactory->createResponse()->withBody($stream);

        return self::range($response, $request->getHeaderLine('Range'));
    }

    /**
     * Handle range requests.
     */
    private static function range(ResponseInterface $response, string $range): ResponseInterface
    {
        $response = $response->withHeader('Accept-Ranges', 'bytes');

        if (empty($range) || !($range = self::parseRangeHeader($range))) {
            return $response;
        }

        list($first, $last) = $range;
        $size = $response->getBody()->getSize();

        if ($last === null) {
            $last = $size - 1;
        }

        return $response
            ->withStatus(206)
            ->withHeader('Content-Length', (string) ($last - $first + 1))
            ->withHeader('Content-Range', sprintf('bytes %d-%d/%d', $first, $last, $size));
    }

    /**
     * Parses a range header, for example: bytes=500-999.
     *
     * @return array{0: int, 1: int|null}|false
     */
    private static function parseRangeHeader(string $header)
    {
        if (preg_match('/bytes=(?P<first>\d+)-(?P<last>\d+)?/', $header, $matches)) {
            return [
                (int) $matches['first'],
                isset($matches['last']) ? (int) $matches['last'] : null,
            ];
        }

        return false;
    }
}

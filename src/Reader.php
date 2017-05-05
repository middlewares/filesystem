<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use RuntimeException;

class Reader extends Filesystem implements MiddlewareInterface
{
    /**
     * @var bool
     */
    private $continueOnError = false;

    /**
     * Configure if continue to the next middleware if the file is not found.
     *
     * @param bool $continueOnError
     *
     * @return self
     */
    public function continueOnError($continueOnError = true)
    {
        $this->continueOnError = $continueOnError;

        return $this;
    }

    /**
     * Process a request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        //Only GET methods are allowed
        if ($request->getMethod() !== 'GET') {
            if ($this->continueOnError) {
                return $delegate->process($request);
            }

            return Utils\Factory::createResponse(405)->withHeader('Allow', 'GET');
        }

        $file = static::getFilename($request->getUri()->getPath());

        if ($this->filesystem->has($file)) {
            return $this->read($request, $file);
        }

        //If the file does not exists, check if is gzipped
        $file .= '.gz';

        if (stripos($request->getHeaderLine('Accept-Encoding'), 'gzip') === false || !$this->filesystem->has($file)) {
            if ($this->continueOnError) {
                return $delegate->process($request);
            }

            return Utils\Factory::createResponse(404);
        }

        return $this->read($request, $file)->withHeader('Content-Encoding', 'gzip');
    }

    /**
     * Read a file and returns a stream.
     *
     * @param ServerRequestInterface $request
     * @param string                 $file
     *
     * @return StreamInterface
     */
    private function read(ServerRequestInterface $request, $file)
    {
        $resource = $this->filesystem->readStream($file);

        if ($resource === false) {
            throw new RuntimeException(sprintf('Unable to read the file "%s"', $file));
        }

        $response = Utils\Factory::createResponse()->withBody(Utils\Factory::createStream($resource));

        return self::range($response, $request->getHeaderLine('Range'));
    }

    /**
     * Handle range requests.
     *
     * @param ResponseInterface $response
     * @param string            $range
     *
     * @return ResponseInterface
     */
    private static function range(ResponseInterface $response, $range)
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
     * @param string $header
     *
     * @return false|array [first, last]
     */
    private static function parseRangeHeader($header)
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

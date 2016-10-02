<?php

namespace Middlewares;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\Middleware\MiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;
use RuntimeException;

class Writer extends Filesystem implements MiddlewareInterface
{
    /**
     * Process a request and return a response.
     *
     * @param RequestInterface  $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);

        if ($this->isWritable($request, $response)) {
            $path = static::getFilename($request->getUri()->getPath());

            //if it's gz compressed, append .gz
            if (strtolower($response->getHeaderLine('Content-Encoding')) === 'gzip') {
                $path .= '.gz';
            }

            $resource = $response->getBody()->detach();

            if ($resource === null) {
                throw new RuntimeException('Error on detach the stream body');
            }

            $this->filesystem->writeStream($path, $resource);

            return $response->withBody(Utils\Factory::createStream($resource));
        }

        return $response;
    }

    /**
     * Check whether the response is writable or not.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return bool
     */
    private function isWritable(RequestInterface $request, ResponseInterface $response)
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

<?php

namespace Middlewares;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class Writer extends Filesystem implements MiddlewareInterface
{
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return bool
     */
    private function isWritable(ServerRequestInterface $request, ResponseInterface $response)
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

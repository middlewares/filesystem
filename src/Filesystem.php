<?php
declare(strict_types = 1);

namespace Middlewares;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemInterface;

abstract class Filesystem
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    protected static function createLocalFlysystem(string $path): FilesystemInterface
    {
        return new Flysystem(new Local($path));
    }

    /**
     * Resolve the filename of the response file.
     */
    protected static function getFilename(string $path): string
    {
        $parts = pathinfo(urldecode($path));
        $path = isset($parts['dirname']) ? $parts['dirname'] : '';
        $filename = isset($parts['basename']) ? $parts['basename'] : '';

        //if has not extension, assume it's a directory and append index.html
        if (empty($parts['extension'])) {
            $filename .= '/index.html';
        }

        return str_replace('//', '/', $path.'/'.$filename);
    }
}

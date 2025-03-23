<?php
declare(strict_types = 1);

namespace Middlewares;

use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;

abstract class Filesystem
{
    /**
     * @var FilesystemOperator
     */
    protected $filesystem;

    protected static function createLocalFlysystem(string $path): FilesystemOperator
    {
        return new Flysystem(new LocalFilesystemAdapter($path));
    }

    /**
     * Resolve the filename of the response file.
     */
    protected static function getFilename(string $path): string
    {
        $parts = pathinfo(urldecode($path));
        $path = $parts['dirname'] ?? '';
        $filename = $parts['basename'];

        //if has not extension, assume it's a directory and append index.html
        if (empty($parts['extension'])) {
            $filename .= '/index.html';
        }

        return str_replace('//', '/', $path.'/'.$filename);
    }
}

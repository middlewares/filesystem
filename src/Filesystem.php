<?php

namespace Middlewares;

use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemInterface;

abstract class Filesystem
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * Configure the root of the filesystem.
     *
     * @param string|FilesystemInterface $filesystem
     */
    public function __construct($filesystem)
    {
        if (is_string($filesystem)) {
            $filesystem = new Flysystem(new Local($filesystem));
        }

        if (!($filesystem instanceof FilesystemInterface)) {
            throw new InvalidArgumentException(
                sprintf('The first argument must be a string or an instance of %s', FilesystemInterface::class)
            );
        }

        $this->filesystem = $filesystem;
    }

    /**
     * Resolve the filename of the response file.
     *
     * @param string $path
     *
     * @return string
     */
    protected static function getFilename($path)
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

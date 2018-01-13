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

    public static function createFromDirectory(string $path): self
    {
        $filesystem = new Flysystem(new Local($path));

        return new static($filesystem);
    }

    /**
     * Configure the root of the filesystem.
     *
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
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

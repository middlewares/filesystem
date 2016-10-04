# middlewares/filesystem

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to save or read responses from files. It uses [Flysystem](http://flysystem.thephpleague.com/) as filesystem handler, so you can use not only a local directories, but also any other adapter like [ftp](http://flysystem.thephpleague.com/adapter/ftp/), [sftp](http://flysystem.thephpleague.com/adapter/sftp/), [dropbox](http://flysystem.thephpleague.com/adapter/dropbox/), etc... This package includes the following components:

* [Reader](#reader)
* [Writer](#Writer)

## Requirements

* PHP >= 5.6
* A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http mesage implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)
* A [PSR-15](https://github.com/http-interop/http-middleware) middleware dispatcher ([Middleman](https://github.com/mindplay-dk/middleman), etc...)

## Installation

This package is installable and autoloadable via Composer as [middlewares/filesystem](https://packagist.org/packages/middlewares/filesystem).

```sh
composer require middlewares/filesystem
```

## Reader

To read the response body from a file under the following conditions:

* Only `GET` methods are allowed, returning a `405` code otherwise.
* If the request path has no extension, assume it's a directory and append `/index.html`. For example: if the request path is `/post/23`, the file used is `/post/23/index.html`.
* It can handle gzipped files. For example, if `/post/23/index.html` does not exists but `/post/23/index.html.gz` is available and the request header `Accept-Encoding` contains `gzip`, returns it.
* `Accept-Ranges` is also supported, useful to server big files like videos.

#### `__construct(string|FilesystemInterface $filesystem)`

Use a string to set the directory in which the files are placed. You can use also an instance of `League\Flysystem\FilesystemInterface`. Example using a ftp storage:

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp;

$filesystem = new Filesystem(new Ftp([
    'host' => 'ftp.example.com',
    'username' => 'username',
    'password' => 'password',
    'port' => 21,
    'root' => '/path/to/root',
    'passive' => true,
    'ssl' => true,
    'timeout' => 30,
]));

$dispatcher = new Dispatcher([
    new Middlewares\Reader($filesystem)
]);

$response = $dispatcher->dispatch(new Request());
```

#### `continueOnError(true)`

Allows to continue to the next middleware on error (file not found, method not allowed, etc). This allows to create a simple caching system as the following:

```php
$cache = '/path/to/files';

$dispatcher = new Dispatcher([
    (new Middlewares\Reader($cache))    //read and returns the cached response...
        ->continueOnError(),            //...but continue if the file does not exists

    new Middlewares\Writer($cache),     //save the response in the cache

    new Middlewares\AuraRouter($route), //create a response using, for example, Aura.Router
]);

$response = $dispatcher->dispatch(new Request());
```

## Writer

Saves the response content into a file if all of the following conditions are met:

* The method is `GET`
* The status code is `200`
* The `Cache-Control` header does not contain `no-cache` and `no-store`

To be compatible with `Reader` behaviour:

* If the request path has no extension, assume it's a directory and append `/index.html`. For example: if the request path is `/post/23`, the file saved is `/post/23/index.html`.
* If the response is gzipped (has the header `Content-Encoding: gzip`) the file is saved with the extension .gz. For example `/post/23/index.html.gz` (instead `/post/23/index.html`).

#### `__construct(string|FilesystemInterface $filesystem)`

Use a string to set the storage directory. You can use also an instance of `League\Flysystem\FilesystemInterface`.

```php
$dispatcher = new Dispatcher([
    new Middlewares\Writer(__DIR__.'/storage')
]);

$response = $dispatcher->dispatch(new Request());
```
---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/filesystem.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/filesystem/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/filesystem.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/filesystem.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/48561559-323f-459d-8ed8-5d7ba81f5652.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/filesystem
[link-travis]: https://travis-ci.org/middlewares/filesystem
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/filesystem
[link-downloads]: https://packagist.org/packages/middlewares/filesystem
[link-sensiolabs]: https://insight.sensiolabs.com/projects/48561559-323f-459d-8ed8-5d7ba81f5652

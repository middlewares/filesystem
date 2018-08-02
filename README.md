# middlewares/filesystem

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to save or read responses from files. It uses [Flysystem](http://flysystem.thephpleague.com/) as filesystem handler, so you can use not only a local directories, but also any other adapter like [ftp](http://flysystem.thephpleague.com/adapter/ftp/), [sftp](http://flysystem.thephpleague.com/adapter/sftp/), [dropbox](http://flysystem.thephpleague.com/adapter/dropbox/), etc... This package includes the following components:

* [Reader](#reader)
* [Writer](#writer)

## Requirements

* PHP >= 7.0
* A [PSR-7 http library](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/filesystem](https://packagist.org/packages/middlewares/filesystem).

```sh
composer require middlewares/filesystem
```

## Example

```php
$dispatcher = new Dispatcher([
    Middlewares\Reader::createFromDirectory(__DIR__.'/assets')
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

## Reader

To read the response body from a file under the following conditions:

* Only `GET` methods are allowed, returning a `405` code otherwise.
* If the request path has no extension, assume it's a directory and append `/index.html`. For example: if the request path is `/post/23`, the file used is `/post/23/index.html`.
* It can handle gzipped files. For example, if `/post/23/index.html` does not exists but `/post/23/index.html.gz` is available and the request header `Accept-Encoding` contains `gzip`, returns it.
* `Accept-Ranges` is also supported, useful to server big files like videos.

#### `__construct(League\Flysystem\FilesystemInterface $filesystem)`

Set the filesystem manager. Example using a ftp storage:

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

$response = $dispatcher->dispatch(new ServerRequest());
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

$response = $dispatcher->dispatch(new ServerRequest());
```

#### `responseFactory(Psr\Http\Message\ResponseFactoryInterface $responseFactory)`

A PSR-17 factory to create the responses.

#### `streamFactory(Psr\Http\Message\StreamFactoryInterface $streamFactory)`

A PSR-17 factory to create the new response bodies.

## Writer

Saves the response content into a file if all of the following conditions are met:

* The method is `GET`
* The status code is `200`
* The `Cache-Control` header does not contain `no-cache` and `no-store`

To be compatible with `Reader` behaviour:

* If the request path has no extension, assume it's a directory and append `/index.html`. For example: if the request path is `/post/23`, the file saved is `/post/23/index.html`.
* If the response is gzipped (has the header `Content-Encoding: gzip`) the file is saved with the extension .gz. For example `/post/23/index.html.gz` (instead `/post/23/index.html`).

#### `__construct(League\Flysystem\FilesystemInterface $filesystem)`

Set the filesystem manager.

```php
$filesystem = new Flysystem(new Local(__DIR__.'/storage'));

$dispatcher = new Dispatcher([
    new Middlewares\Writer($filesystem)
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

#### `streamFactory(Psr\Http\Message\StreamFactoryInterface $streamFactory)`

A PSR-17 factory to create the new response bodies.

## Helpers

#### `createFromDirectory(string $path)`

Both `Reader` and `Writer` have a static method as a shortcut to create instances using a directory in the local filesystem, due this is the most common case:

```php
$dispatcher = new Dispatcher([
    Middlewares\Writer::createFromDirectory(__DIR__.'/assets')
    Middlewares\Reader::createFromDirectory(__DIR__.'/assets')
]);
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

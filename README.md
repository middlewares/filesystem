# middlewares/filesystem

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
![Testing][ico-ga]
[![Total Downloads][ico-downloads]][link-downloads]

Middleware to save or read responses from files. It uses [Flysystem](http://flysystem.thephpleague.com/) as filesystem handler, so you can use not only a local directories, but also any other adapter like [ftp](http://flysystem.thephpleague.com/adapter/ftp/), [sftp](http://flysystem.thephpleague.com/adapter/sftp/), [dropbox](http://flysystem.thephpleague.com/adapter/dropbox/), etc... This package includes the following components:

* [Reader](#reader)
* [Writer](#writer)

## Requirements

* PHP >= 7.2
* A [PSR-7 http library](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/filesystem](https://packagist.org/packages/middlewares/filesystem).

```sh
composer require middlewares/filesystem
```

## Example

```php
Dispatcher::run([
    Middlewares\Reader::createFromDirectory(__DIR__.'/assets')
]);
```

## Reader

To read the response body from a file under the following conditions:

* Only `GET` methods are allowed, returning a `405` code otherwise.
* If the request path has no extension, assume it's a directory and append `/index.html`. For example: if the request path is `/post/23`, the file used is `/post/23/index.html`.
* It can handle gzipped files. For example, if `/post/23/index.html` does not exists but `/post/23/index.html.gz` is available and the request header `Accept-Encoding` contains `gzip`, returns it.
* `Accept-Ranges` is also supported, useful to server big files like videos.

Example using a ftp storage:

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;

$adapter = new League\Flysystem\Ftp\FtpAdapter(
    FtpConnectionOptions::fromArray([
        'host' => 'hostname',
        'root' => '/root/path/',
        'username' => 'username',
        'password' => 'password',
        'port' => 21,
        'ssl' => false,
        'timeout' => 90,
        'utf8' => false,
        'passive' => true,
        'transferMode' => FTP_BINARY,
        'systemType' => null, // 'windows' or 'unix'
        'ignorePassiveAddress' => null, // true or false
        'timestampsOnUnixListingsEnabled' => false, // true or false
        'recurseManually' => true // true 
    ])
);

// The FilesystemOperator
$filesystem = new Filesystem($adapter);

Dispatcher::run([
    new Middlewares\Reader($filesystem)
]);
```

Optionally, you can provide a `Psr\Http\Message\ResponseFactoryInterface` and `Psr\Http\Message\StreamFactoryInterface`, that will be used to create the response and stream. If they are not not defined, [Middleware\Utils\Factory](https://github.com/middlewares/utils#factory) will be used to detect them automatically.

```php
$responseFactory = new MyOwnResponseFactory();
$streamFactory = new MyOwnStreamFactory();

$reader = new Middlewares\Reader($filesystem, $responseFactory, $streamFactory);
```

### continueOnError

Allows to continue to the next middleware on error (file not found, method not allowed, etc). This allows to create a simple caching system as the following:

```php
$cache = new Filesystem(new LocalFilesystemAdapter(__DIR__.'/path/to/files'));

Dispatcher::run([
    (new Middlewares\Reader($cache))    //read and returns the cached response...
        ->continueOnError(),            //...but continue if the file does not exists

    new Middlewares\Writer($cache),     //save the response in the cache

    new Middlewares\AuraRouter($route), //create a response using, for example, Aura.Router
]);
```

## Writer

Saves the response content into a file if all of the following conditions are met:

* The method is `GET`
* The status code is `200`
* The `Cache-Control` header does not contain `no-cache` and `no-store`

To be compatible with `Reader` behaviour:

* If the request path has no extension, assume it's a directory and append `/index.html`. For example: if the request path is `/post/23`, the file saved is `/post/23/index.html`.
* If the response is gzipped (has the header `Content-Encoding: gzip`) the file is saved with the extension .gz. For example `/post/23/index.html.gz` (instead `/post/23/index.html`).

```php
$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__.'/storage'));

Dispatcher::run([
    new Middlewares\Writer($filesystem)
]);
```

Optionally, you can provide a `Psr\Http\Message\StreamFactoryInterface` as the second that will be used to create a new body to the response. If it's not defined, [Middleware\Utils\Factory](https://github.com/middlewares/utils#factory) will be used to detect it automatically.

```php
$streamFactory = new MyOwnStreamFactory();

$reader = new Middlewares\Writer($filesystem, $streamFactory);
```

## Helpers

### createFromDirectory

Both `Reader` and `Writer` have a static method as a shortcut to create instances using a directory in the local filesystem, due this is the most common case:

```php
Dispatcher::run([
    Middlewares\Writer::createFromDirectory(__DIR__.'/assets')
    Middlewares\Reader::createFromDirectory(__DIR__.'/assets')
]);
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/filesystem.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ga]: https://github.com/middlewares/filesystem/workflows/testing/badge.svg
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/filesystem.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/filesystem
[link-downloads]: https://packagist.org/packages/middlewares/filesystem

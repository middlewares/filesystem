# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [3.0.0] - 2020-12-03
### Changed
- PHP 7.2 remains th  minimum version.
- Updated `league/filesystem` to version 2 for php 7.2, 7.3 and 7.4, and to version 3 for >= php 8.0.
- With these changes, a breaking change is introduced by `league/filesystem`. Now you have to pass a FilesystemOperator interface compliant class to the middlewares. Check README's code examples.

## [2.0.1] - 2020-12-03
### Added
- Support for PHP 8.0

## [2.0.0] - 2019-11-30
### Added
- Arguments to `Reader` constructor to define a `ResponseFactory` and `StreamFactory`.
- Argument to `Writer` constructor to define a `StreamFactory`.

### Removed
- Support for PHP 7.0 and 7.1
- Option `responseFactory` in `Reader`, use the constructor argument
- Option `streamFactory` in `Reader` and `Writer`, use the constructor argument

## [1.1.0] - 2018-08-04
### Added
- PSR-17 support
- New option `responseFactory` in `Reader`
- New option `streamFactory` in `Reader` and `Writer`

## [1.0.0] - 2017-01-25
### Added
- New static function `createFromDirectory` in `Reader` and `Writer` to create instances using a local directory
- Improved testing and added code coverage reporting
- Added tests for PHP 7.2

### Changed
- Upgraded to the final version of PSR-15 `psr/http-server-middleware`
- changed the constructor signature: removed the ability to provide a string as a first argument.

### Fixed
- Updated license year

## [0.5.0] - 2017-11-13
### Changed
- Replaced `http-interop/http-middleware` with  `http-interop/http-server-middleware`.

### Removed
- Removed support for PHP 5.x.

## [0.4.0] - 2017-09-21
### Changed
- Append `.dist` suffix to phpcs.xml and phpunit.xml files
- Changed the configuration of phpcs and php_cs
- Upgraded phpunit to the latest version and improved its config file
- Updated to `http-interop/http-middleware#0.5`

## [0.3.1] - 2017-05-06
### Fixed
- `Middlewares\Reader` adds the header `Allow: GET` in `405` responses

## [0.3.0] - 2016-12-26
### Changed
- Updated tests
- Updated to `http-interop/http-middleware#0.4`
- Updated `friendsofphp/php-cs-fixer#2.0`

## [0.2.0] - 2016-11-22
### Changed
- Updated to `http-interop/http-middleware#0.3`

## 0.1.0 - 2016-10-02
First version

[3.0.0]: https://github.com/middlewares/filesystem/compare/v2.0.1...v3.0.0
[2.0.1]: https://github.com/middlewares/filesystem/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/middlewares/filesystem/compare/v1.1.0...v2.0.0
[1.1.0]: https://github.com/middlewares/filesystem/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/middlewares/filesystem/compare/v0.5.0...v1.0.0
[0.5.0]: https://github.com/middlewares/filesystem/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/middlewares/filesystem/compare/v0.3.1...v0.4.0
[0.3.1]: https://github.com/middlewares/filesystem/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/middlewares/filesystem/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/middlewares/filesystem/compare/v0.1.0...v0.2.0

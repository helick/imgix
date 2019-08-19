# Helick Imgix

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE.md)
[![Quality Score][ico-code-quality]][link-code-quality]

Imgix integration.

## Requirements

Make sure all dependencies have been installed before moving on:

* [PHP](http://php.net/manual/en/install.php) >= 7.1
* [Composer](https://getcomposer.org/download/)

## Install

Install via Composer:

``` bash
$ composer require helick/imgix
```

Update your application config by declaring the following:

``` php
Config::define('IMGIX_DOMAIN', env('IMGIX_DOMAIN'));
```

Update your env variables by declaring the following:

``` dotenv
IMGIX_DOMAIN=demo.imgix.net
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email evgenii@helick.io instead of using the issue tracker.

## Credits

- [Evgenii Nasyrov][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/helick/imgix.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/helick/imgix.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/helick/imgix.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/helick/imgix
[link-code-quality]: https://scrutinizer-ci.com/g/helick/imgix
[link-downloads]: https://packagist.org/packages/helick/imgix
[link-author]: https://github.com/nasyrov
[link-contributors]: ../../contributors

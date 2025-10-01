# Implementation of the Ponto API in PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alchemicstudio/ponto-php.svg?style=flat-square)](https://packagist.org/packages/alchemicstudio/ponto-php)
[![Tests](https://img.shields.io/github/actions/workflow/status/alchemicstudio/ponto-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/alchemicstudio/ponto-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/alchemicstudio/ponto-php.svg?style=flat-square)](https://packagist.org/packages/alchemicstudio/ponto-php)

Implementation of the [Ponto API](https://documentation.myponto.com/1/api/curl) in PHP

## Installation

You can install the package via composer:

```bash
composer require alchemicstudio/ponto-php
```

## Usage

```php
$skeleton = new AlchemicStudio\Ponto();
echo $skeleton->echoPhrase('Hello, AlchemicStudio!');
```

## Testing

The project uses Pest for testing. You can run the tests with:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Sébastien Denooz](https://github.com/AlchemicStudio)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

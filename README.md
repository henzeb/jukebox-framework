# Jukebox Crawler Framework

[![Latest Version on Packagist](https://img.shields.io/packagist/v/henzeb/jukebox-crawler-framework.svg?style=flat-square)](https://packagist.org/packages/henzeb/query-filter-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/henzeb/jukebox-crawler-framework.svg?style=flat-square)](https://packagist.org/packages/henzeb/query-filter-builder)

If you ever need to build a crawler from scratch, there are a lot of choices.
Most however just focuses on crawling and simply allows you to do some action when 
crawled.

I found the best package was Spatie's [crawler](https://github.com/spatie/crawler),
but it lacked some logic. For one: Why have a single profile and multiple observers? 
Why writing url check logic twice? This is where the idea for Jukebox was born. 

This package contains everything a crawler would ever need. It is using Spatie's 
crawler under the hood.

## Installation
You can install the package standalone, or inside an (existing) laravel project.

```bash
composer require henzeb/jukebox-crawler-framework
```

## Usage


### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email henzeberkheij@gmail.com instead of using the issue tracker.

## Credits

- [Henze Berkheij](https://github.com/henzeb)

## License

The GNU AGPLv. Please see [License File](LICENSE.md) for more information.

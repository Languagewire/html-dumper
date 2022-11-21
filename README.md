LanguageWire HtmlDumper library
=====================================
[![Packagist](https://img.shields.io/packagist/v/languagewire/html-dumper)](https://packagist.org/packages/languagewire/html-dumper)
[![Build](https://github.com/Languagewire/html-dumper/actions/workflows/build.yml/badge.svg)](https://github.com/Languagewire/html-dumper/actions/workflows/build.yml)
[![Coverage Status](https://coveralls.io/repos/github/Languagewire/html-dumper/badge.svg)](https://coveralls.io/github/Languagewire/html-dumper)
[![license](https://img.shields.io/packagist/l/languagewire/html-dumper)](https://github.com/Languagewire/html-dumper/blob/main/LICENSE)

HtmlDumper is a PHP library which downloads a copy of an HTML page and its assets into a target directory.

- Downloads HTML source code and transforms all URIs into relative paths, creating an updated `index.html` file.
- Parses HTML and fetches relevant resources
  - Stylesheets, scripts, images, videos
  - Also works with assets located within CSS files.
- Removes anchor links to external pages.
- Does not crawl pages beyond the initial URL.

```php
$url = "https://example.com";
$targetDirectory = "/tmp/htmldump";

$downloader = new \LanguageWire\HtmlDumper\Service\PageDownloader();
if ($downloader->download($url, $targetDirectory)) {
    echo "Sucessfully downloaded $url in $targetDirectory";
}
```

## Requirements

* PHP 7.2+
* [PHP DOM Extension](https://www.php.net/manual/en/intro.dom.php)
* [Composer](https://getcomposer.org/)

## Installation

The recommended way to install HtmlDumper is through [Composer](https://getcomposer.org/).

```bash
composer require languagewire/html-dumper
```

## Development

In the `build/` folder there is a `Dockerfile` file which sets up all dependencies needed for local development, runs unit tests and other linters.

Customize `build/.env` like this:

```bash
cd build
cp .env.template .env
nano .env
```

And then run `./build.sh` within the `build/` folder:

```bash
cd build
./build.sh
```

## License

HtmlDumper is made available under the MIT License (MIT). Please see the [LICENSE](LICENSE) file for more information.

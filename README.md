LanguageWire HtmlDumper library
=====================================
![Version 0.6.0](https://img.shields.io/badge/version-0.6.0-blue)
![Tests passing](https://img.shields.io/badge/tests-passing-brightgreen)
![Coverage 97.95%](https://img.shields.io/badge/coverage-97.95%25-brightgreen)
![License: MIT](https://img.shields.io/badge/license-MIT-blue)

HtmlDumper is a PHP library which downloads a copy of an HTML page and its assets into a target directory. It is used at LanguageWire to create static versions of documents used as translation contexts.

- Downloads HTML source code and transforms all URIs into relative paths, creating an updated `index.html` file.
- Parses HTML and fetches relevant resources
  - Stylesheets, scripts, images, videos
  - Also works with assets located within CSS files.
- Removes anchor links to external pages.
- Does not crawl beyond the initial URL.

```php
$url = "https://www.languagewire.com/en/about-us/locations";
$targetDirectory = "/tmp/htmldump";

$downloader = new \LanguageWire\HtmlDumper\Service\PageDownloader();
if ($downloader->download($url, $targetDirectory)) {
    echo "Sucessfully downloaded $url in $targetDirectory";
}
```

## Requirements

* PHP 7.2+
* [Composer](https://getcomposer.org/)

## Installation

The recommended way to install HtmlDumper is through [Composer](https://getcomposer.org/).

```bash
composer require languagewire/htmldumper
```


## Development

```bash
cd build
cp .env.template .env
./build.sh
```

## License

HtmlDumper is made available under the MIT License (MIT). Please see the [LICENSE](LICENSE) file for more information.


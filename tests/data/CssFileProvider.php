<?php

declare(strict_types=1);

/*
 * This file is part of the LanguageWire HtmlDumper library.
 *
 * (c) LanguageWire <contact@languagewire.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 */

namespace data\LanguageWire;

class CssFileProvider extends FileProviderBase
{
    /**
     * @var array[]
     */
    private $testCssFiles;

    public function __construct()
    {
        $this->testCssFiles = $this->mapToPHPUnitProviderFormat([
            'no urls' => [
                "tests/data/css/noUrls.css",
                []
            ],
            'one url' => [
                "tests/data/css/oneUrl.css",
                ['data/img/image.png']
            ],
            'many url' => [
                "tests/data/css/manyUrls.css",
                ['data/img/image1.png', 'data/img/image2.png']
            ],
            'duplicated urls' => [
                "tests/data/css/duplicatedUrls.css",
                ['data/img/image.png']
            ]
        ]);
    }

    public function getTestCssFiles(): array
    {
        return $this->testCssFiles;
    }

    public function getValidCssWithAssets(): string
    {
        return $this->readFile("tests/data/css/oneUrl.css");
    }

    public function getTestCssFilesUriTransformBefore(): string
    {
        return $this->readFile("tests/data/css/uriTransform/before.css");
    }

    public function getTestCssFilesUriTransformAfter(): string
    {
        return $this->readFile("tests/data/css/uriTransform/after.css");
    }
}

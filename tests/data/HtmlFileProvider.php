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

class HtmlFileProvider
{
    /**
     * @var array[]
     */
    private $testHtmlFilesWithoutAssets;
    /**
     * @var array[]
     */
    private $testHtmlFilesWithAssets;

    public function __construct()
    {
        $this->testHtmlFilesWithoutAssets = $this->mapToPHPUnitProviderFormat([
            'minimal html' => [
                "tests/data/html/singleElement/minimal.html",
                []
            ]
        ]);
        $this->testHtmlFilesWithAssets = $this->mapToPHPUnitProviderFormat([
            'one image' => [
                "tests/data/html/singleElement/withImage.html",
                ['data/img/screenshot.png']
            ],
            'many images' => [
                "tests/data/html/singleElement/withMultipleImages.html",
                [
                    'data/img/screenshot1.png',
                    'data/img/screenshot2.png',
                    'data/img/screenshot3.png',
                    'data/img/svg.svg',
                ]
            ],
            'duplicated images' => [
                "tests/data/html/singleElement/duplicated.html",
                ['data/img/screenshot.png']
            ],
            'one video with a poster attribute' => [
                "tests/data/html/singleElement/withVideoPosters.html",
                ['data/img/video_poster.png']
            ],
            'css files' => [
                "tests/data/html/singleElement/withStylesheets.html",
                ['data/css/stylesheet1.css', 'data/css/stylesheet2.css']
            ],
            'malformed css tag' => [
                "tests/data/html/singleElement/withInvalidStylesheets.html",
                []
            ],
            'preload elements' => [
                "tests/data/html/singleElement/preload.html",
                ['data/css/stylesheet.css', 'data/fonts/font.woff2']
            ],
            'encoded urls' => [
                "tests/data/html/singleElement/urldecode.html",
                ['data/img/screenshot with spaces.png']
            ],
            'scripts' => [
                "tests/data/html/singleElement/withScripts.html",
                ['data/js/script1.js', 'data/js/script2.js']
            ],
            'favicon' => [
                "tests/data/html/singleElement/withFavicon.html",
                [
                    'data/img/favicon.png',
                    'data/img/image1.png',
                    'data/img/image2.png',
                    'data/img/image3.png',
                    'data/img/image4.png',
                ]
            ],
            'meta media' => [
                "tests/data/html/singleElement/withMetaMedia.html",
                ['data/img/image1.png', 'data/img/image2.png', 'data/img/image3.png']
            ],
            'complete page' => [
                "tests/data/html/multipleElements/completePage.html",
                [
                    'data/img/favicon.png',
                    'data/css/stylesheet1.css',
                    'data/css/stylesheet2.css',
                    'data/img/image1.png',
                    'data/img/image2.png',
                    'data/img/image3.png',
                    'data/js/script1.js',
                    'data/img/screenshot1.png',
                    'data/img/screenshot2.png',
                    'data/img/video_poster.png',
                    'data/js/script2.js',
                ]
            ],
            'relative path' => [
                "tests/data/html/urlHandling/relativePath.html",
                ['data/img/screenshot.png']
            ],
            'full url' => [
                "tests/data/html/urlHandling/fullUrl.html",
                ['http://example.com/data/img/screenshot1.png', 'https://example.com/data/img/screenshot2.png']
            ],
        ]);
    }

    public function getAllTestHtmlFiles(): array
    {
        return array_merge($this->testHtmlFilesWithoutAssets, $this->testHtmlFilesWithAssets);
    }

    public function getMinimalValidHtml(): string
    {
        return file_get_contents("tests/data/html/singleElement/minimal.html");
    }

    public function getValidHtmlWithAssets(): string
    {
        return file_get_contents("tests/data/html/singleElement/withMultipleImages.html");
    }

    public function getTestHtmlFilesUriTransformBefore(): string
    {
        return file_get_contents("tests/data/html/urlHandling/uriTransform/before.html");
    }

    public function getTestHtmlFilesUriTransformAfter(): string
    {
        return file_get_contents("tests/data/html/urlHandling/uriTransform/after.html");
    }

    public function getTestHtmlFilesAnchorBefore(): string
    {
        return file_get_contents("tests/data/html/removeAnchors/before.html");
    }

    public function getTestHtmlFilesAnchorAfter(): string
    {
        return file_get_contents("tests/data/html/removeAnchors/after.html");
    }

    public function getTestHtmlFilesSrcSetsBefore(): string
    {
        return file_get_contents("tests/data/html/removeSrcSets/before.html");
    }

    public function getTestHtmlFilesSrcSetsAfter(): string
    {
        return file_get_contents("tests/data/html/removeSrcSets/after.html");
    }

    private function mapToPHPUnitProviderFormat(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = [
                file_get_contents($value[0]),
                $value[1]
            ];
        }

        return $result;
    }
}
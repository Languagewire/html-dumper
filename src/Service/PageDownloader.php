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

namespace LanguageWire\HtmlDumper\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use LanguageWire\HtmlDumper\Http\NullableHttpClient;
use LanguageWire\HtmlDumper\IO\Filesystem;
use LanguageWire\HtmlDumper\Parser\HtmlParser;
use LanguageWire\HtmlDumper\Uri\UriConverter;

class PageDownloader
{
    /**
     * @var NullableHttpClient
     */
    private $httpClient;
    /**
     * @var UriConverter
     */
    private $uriConverter;
    /**
     * @var HtmlParser
     */
    private $htmlParser;
    /**
     * @var AssetDownloader|null
     */
    private $assetDownloader;
    /**
     * @var Filesystem|null
     */
    private $filesystem;

    public function __construct(
        ClientInterface $httpClient = null,
        Filesystem $filesystem = null,
        UriConverter $uriConverter = null,
        HtmlParser $htmlParser = null,
        AssetDownloader $assetDownloader = null
    ) {
        $standardHttpClient = $httpClient ?? new Client();
        $this->httpClient = new NullableHttpClient($standardHttpClient);
        $this->filesystem = $filesystem ?? new Filesystem();
        $this->uriConverter = $uriConverter ?? new UriConverter();
        $this->htmlParser = $htmlParser ?? new HtmlParser($this->uriConverter);
        $this->assetDownloader = $assetDownloader ?? new AssetDownloader();
    }

    /**
     * Download the HTML of the target page and all assets referenced by it, store them in $targetDirectory
     *
     * @param string $url
     * @param string $targetDirectory
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \LanguageWire\HtmlDumper\Parser\HtmlParsingException
     * @throws \LanguageWire\HtmlDumper\Parser\HtmlGenerationException
     * @throws \Exception
     */
    public function download(string $url, string $targetDirectory): bool
    {
        $baseDomain = $this->uriConverter->getBaseDomainFromUrl($url);

        $indexFileResponse = $this->httpClient->request("GET", $url);

        if ($indexFileResponse == null) {
            return false;
        }

        $parseResult = $this->htmlParser->parseHtmlContent((string) $indexFileResponse->getBody(), $baseDomain);

        $indexHtmlPath = $this->uriConverter->joinPaths($targetDirectory, '/index.html');
        $this->filesystem->createFile($indexHtmlPath, $parseResult->getOutputCode(), true);

        $this->assetDownloader->downloadAssets($parseResult->getAssetUris(), $targetDirectory, $baseDomain);

        return true;
    }
}

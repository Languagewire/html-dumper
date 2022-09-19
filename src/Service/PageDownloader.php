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
use LanguageWire\HtmlDumper\Parser\CssParser;
use LanguageWire\HtmlDumper\Parser\HtmlParser;
use LanguageWire\HtmlDumper\Parser\ParseResult;
use LanguageWire\HtmlDumper\Uri\UriConverter;
use Psr\Http\Message\StreamInterface;

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
     * @var CssParser
     */
    private $cssParser;

    public function __construct(
        ClientInterface $httpClient = null,
        UriConverter $uriConverter = null,
        HtmlParser $htmlParser = null,
        CssParser $cssParser = null
    ) {
        $this->httpClient = new NullableHttpClient($httpClient ?? new Client());
        $this->uriConverter = $uriConverter ?? new UriConverter();
        $this->htmlParser = $htmlParser ?? new HtmlParser($this->uriConverter);
        $this->cssParser = $cssParser ?? new CssParser($this->uriConverter);
    }

    /**
     * Download the HTML of the target page and all assets referenced by it, store them in $targetDirectory
     *
     * @param string $url
     * @param string $targetDirectory
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \LanguageWire\HtmlDumper\Parser\HtmlParsingException
     */
    public function download(string $url, string $targetDirectory): bool
    {
        $this->createTargetDirectory($targetDirectory);
        $baseDomain = $this->uriConverter->getBaseDomainFromUrl($url);

        $parseResult = $this->getProcessedIndexFile($url, $targetDirectory, $baseDomain);

        if ($parseResult == null) {
            return false;
        }

        $this->downloadAssets($parseResult->getAssetUris(), $targetDirectory, $baseDomain);

        return true;
    }

    /**
     * Download the requested URL, parse all assets and store it with all URIs changed to relative paths
     *
     * @param string $url
     * @param string $targetBaseDirectory
     * @param string $baseDomain
     * @return ParseResult|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \LanguageWire\HtmlDumper\Parser\HtmlParsingException
     */
    private function getProcessedIndexFile(
        string $url,
        string $targetBaseDirectory,
        string $baseDomain
    ): ?ParseResult {
        $indexFileResponse = $this->httpClient->request("GET", $url);

        if ($indexFileResponse == null) {
            return null;
        }

        $result = $this->htmlParser->parseHtmlContent((string) $indexFileResponse->getBody(), $baseDomain);

        $this->storeContent($result->getOutputCode(), $targetBaseDirectory, '/index.html');

        return $result;
    }

    /**
     * Store a given stream into a file
     *
     * @param StreamInterface $contentStream
     * @param string $targetBaseDirectory
     * @param string $relativeTargetPath
     * @return string
     */
    private function storeContent(
        StreamInterface $contentStream,
        string $targetBaseDirectory,
        string $relativeTargetPath
    ): string {
        $relativeTargetPath = $this->uriConverter->cleanRelativePath($relativeTargetPath);
        $targetPath = $this->uriConverter->joinPaths($targetBaseDirectory, $relativeTargetPath);

        $targetDirectory = dirname($targetPath);
        if (!\is_dir($targetDirectory)) {
            @mkdir($targetDirectory, 0777, true);
        }

        file_put_contents($targetPath, $contentStream);
        $contentStream->rewind();

        return $targetPath;
    }

    /**
     * Create a target directory, throw an exception if it already exists
     *
     * @param string $targetDirectory
     * @return void
     * @throws \Exception
     */
    private function createTargetDirectory(string $targetDirectory): void
    {
        if (\is_dir($targetDirectory)) {
            throw new \InvalidArgumentException("Target directory $targetDirectory already exists");
        }

        $result = @mkdir($targetDirectory);

        if ($result === false) {
            throw new \Exception("Could not create target directory $targetDirectory");
        }
    }

    /**
     * Download all given assets, converting URIs to relative paths
     * Also parse CSS, look for assets and call this method recursively
     *
     * @param string[] $assetUris
     * @param string $targetDirectory
     * @param string $baseDomain
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function downloadAssets(array $assetUris, string $targetDirectory, string $baseDomain): void
    {
        foreach ($assetUris as $assetUri) {
            $offlinePath = $this->uriConverter->convertUriToOfflinePath($assetUri, $baseDomain);
            if (pathinfo($offlinePath, PATHINFO_EXTENSION) == null) {
                // Nothing to download without an extension
                continue;
            }

            $assetUrl = $this->uriConverter->convertUriToUrl($assetUri, $baseDomain);

            $targetPath = $this->downloadAsset($assetUrl, $targetDirectory, $baseDomain);

            if ($targetPath == null) {
                continue;
            }

            if (pathinfo($targetPath, PATHINFO_EXTENSION) === 'css') {
                $contents = file_get_contents($targetPath);

                $depthLevel = $this->uriConverter->countDepthLevelOfPath($offlinePath);
                $parseResult = $this->cssParser->parseCssContent($contents, $baseDomain, $depthLevel);

                // Store updated CSS
                file_put_contents($targetPath, $parseResult->getOutputCss());

                // Recursively call `downloadAssets` with the assetPaths found within the css file
                $this->downloadAssets($parseResult->getAssetUris(), $targetDirectory, $baseDomain);
            }
        }
    }

    /**
     * Download an asset and store it
     *
     * @param string $assetUrl
     * @param string $targetDirectory
     * @param string $baseDomain
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function downloadAsset(string $assetUrl, string $targetDirectory, string $baseDomain): ?string
    {
        $assetResponse = $this->httpClient->request("GET", $assetUrl);

        if ($assetResponse == null) {
            // Do nothing, leave the broken URL for now
            return null;
        }

        $offlinePath = $this->uriConverter->convertUriToOfflinePath($assetUrl, $baseDomain);
        return $this->storeContent($assetResponse->getBody(), $targetDirectory, $offlinePath);
    }
}

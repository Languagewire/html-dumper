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
use LanguageWire\HtmlDumper\Parser\CssParser;
use LanguageWire\HtmlDumper\Uri\UriConverter;

class AssetDownloader
{
    /**
     * @var NullableHttpClient
     */
    private $httpClient;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var UriConverter
     */
    private $uriConverter;
    /**
     * @var CssParser
     */
    private $cssParser;

    public function __construct(
        ClientInterface $httpClient = null,
        Filesystem $filesystem = null,
        UriConverter $uriConverter = null,
        CssParser $cssParser = null
    ) {
        $this->httpClient = new NullableHttpClient($httpClient ?? new Client());
        $this->filesystem = $filesystem ?? new Filesystem();
        $this->uriConverter = $uriConverter ?? new UriConverter();
        $this->cssParser = $cssParser ?? new CssParser($this->uriConverter);
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
     * @throws \LanguageWire\HtmlDumper\IO\IOException
     */
    public function downloadAssets(array $assetUris, string $targetDirectory, string $baseDomain): void
    {
        foreach ($assetUris as $assetUri) {
            $offlinePath = $this->uriConverter->convertUriToOfflinePath($assetUri, $baseDomain);

            if (pathinfo($offlinePath, PATHINFO_EXTENSION) == null) {
                // Nothing to download without an extension
                continue;
            }

            $assetUrl = $this->uriConverter->convertUriToUrl($assetUri, $baseDomain);

            $assetTargetPath = $this->downloadAsset($assetUrl, $targetDirectory, $baseDomain);

            if ($assetTargetPath == null) {
                continue;
            }

            if (pathinfo($assetTargetPath, PATHINFO_EXTENSION) === 'css') {
                $this->downloadCssAssets($assetTargetPath, $baseDomain, $targetDirectory);
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
     * @throws \LanguageWire\HtmlDumper\IO\IOException
     */
    private function downloadAsset(string $assetUrl, string $targetDirectory, string $baseDomain): ?string
    {
        $assetResponse = $this->httpClient->request("GET", $assetUrl);

        if ($assetResponse == null) {
            // Do nothing, there will be a broken URL in index.html
            return null;
        }

        $targetPath = $this->uriConverter->convertAssertUrlToLocalPath($assetUrl, $baseDomain, $targetDirectory);

        $this->filesystem->writeToFile($targetPath, (string) $assetResponse->getBody(), true);

        return $targetPath;
    }

    /**
     * @param string $cssFilePath
     * @param string $baseDomain
     * @param string $targetDirectory
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \LanguageWire\HtmlDumper\IO\IOException
     */
    private function downloadCssAssets(string $cssFilePath, string $baseDomain, string $targetDirectory): void
    {
        $cssDirectoryPath = $this->filesystem->getParentDirectory($cssFilePath);
        $cssContent = $this->filesystem->readFile($cssFilePath);

        $parseResult = $this->cssParser->parseCssContent($cssContent, $cssDirectoryPath, $baseDomain);

        // Store updated CSS
        $this->filesystem->writeToFile($cssFilePath, $parseResult->getParsedCode());

        // Recursively call `downloadAssets` with the assetPaths found within the css file
        $this->downloadAssets($parseResult->getAssetUris(), $targetDirectory, $baseDomain);
    }
}

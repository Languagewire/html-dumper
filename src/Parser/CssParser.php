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

namespace LanguageWire\HtmlDumper\Parser;

use LanguageWire\HtmlDumper\Uri\UriConverter;

class CssParser
{
    /**
     * @var UriConverter
     */
    private $uriConverter;

    public function __construct(UriConverter $uriConverter)
    {
        $this->uriConverter = $uriConverter;
    }

    /**
     * @param string $cssContent
     * @param string $cssDirectoryPath
     * @param string $baseDomain
     * @return ParseResult
     */
    public function parseCssContent(string $cssContent, string $cssDirectoryPath, string $baseDomain): ParseResult
    {
        $assetUrls = $this->getUris($cssContent);
        $offlinePaths = $this->assetUrlsToOfflinePaths($assetUrls, $cssDirectoryPath, $baseDomain);
        $outputCss = $this->updateCss($cssContent, $offlinePaths);

        return new ParseResult($outputCss, $assetUrls);
    }

    /**
     * @param string $cssContent
     * @return string[]
     */
    private function getUris(string $cssContent): array
    {
        $re = '/url\s*\(\s*[\'"]?(?:(?!data:))([^\'"]+?)[\'"]?\s*\)/im';

        $matches = [];
        preg_match_all($re, $cssContent, $matches, PREG_SET_ORDER);

        $assetUrls = [];
        foreach ($matches as $match) {
            if (count($match) != 2) {
                continue;
            }

            $url = $match[1];
            $assetUrls[] = $url;
        }

        return array_unique($assetUrls);
    }

    /**
     * @param string $cssContent
     * @param string[] $assetUrls
     * @return string
     */
    private function updateCss(string $cssContent, array $assetUrls): string
    {
        foreach ($assetUrls as $assetUrl => $absoluteOfflinePath) {
            $re = '/url\s*\(\s*[\'"]?' . preg_quote($assetUrl, '/') . '[\'"]?\s*\)/im';

            $cssContent = preg_replace($re, sprintf("url('%s')", $absoluteOfflinePath), $cssContent);
        }

        return $cssContent;
    }

    /**
     * @param string[] $assetUrls
     * @param string $cssDirectoryPath
     * @param string $baseDomain
     * @return string[]
     */
    private function assetUrlsToOfflinePaths(array $assetUrls, string $cssDirectoryPath, string $baseDomain): array
    {
        $result = [];

        foreach ($assetUrls as $assetUrl) {
            $relativeOfflinePath = $this->uriConverter->convertUriToOfflinePath($assetUrl, $baseDomain);
            $relativeOfflinePath = $this->uriConverter->removeQueryParams($relativeOfflinePath);
            $absoluteOfflinePath = $this->uriConverter->joinPaths($cssDirectoryPath, $relativeOfflinePath);

            $result[$assetUrl] = $absoluteOfflinePath;
        }

        return $result;
    }
}

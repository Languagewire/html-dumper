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
     * @param string $baseDomain
     * @param int $depthLevel
     * @return CssParseResult
     */
    public function parseCssContent(string $cssContent, string $baseDomain, int $depthLevel = 0): CssParseResult
    {
        $assetUrls = $this->getUris($cssContent);
        $outputCss = $this->updateCss($cssContent, $assetUrls, $baseDomain, $depthLevel);
        $sanitizedAssetUrls = array_map('urldecode', $assetUrls);

        return new CssParseResult($outputCss, $sanitizedAssetUrls);
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
     * @param string $baseDomain
     * @param int $depthLevel
     * @return string
     */
    private function updateCss(string $cssContent, array $assetUrls, string $baseDomain, int $depthLevel): string
    {
        foreach ($assetUrls as $assetUrl) {
            $relativePath = $this->uriConverter->convertUriToOfflinePath($assetUrl, $baseDomain);
            $relativePath = $this->uriConverter->cleanRelativePath($relativePath);

            if (substr_count($relativePath, "../") == 0) {
                $relativePath = $this->uriConverter->prependParentDirectoryDoubleDots($relativePath, $depthLevel);
            }

            $re = '/url\s*\(\s*[\'"]?' . preg_quote($assetUrl, '/') . '[\'"]?\s*\)/im';

            $cssContent = preg_replace($re, sprintf("url('%s')", $relativePath), $cssContent);
        }

        return $cssContent;
    }
}

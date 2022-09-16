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

class HtmlParser
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
     * @param string $htmlContent
     * @param string $baseDomain
     * @return HtmlParseResult
     * @throws HtmlParsingException
     */
    public function parseHtmlContent(string $htmlContent, string $baseDomain): HtmlParseResult
    {
        $document = new \DOMDocument();
        if (!@$document->loadHTML($htmlContent)) {
            throw new HtmlParsingException("Could not load HTML content");
        }

        $xpath = new \DOMXPath($document);

        $this->deleteAnchorHrefs($xpath);
        $this->deleteSrcSets($xpath);

        $assetUrls = array_merge(
            $this->getMedia($xpath, $baseDomain),
            $this->getPreload($xpath, $baseDomain),
            $this->getCss($xpath, $baseDomain),
            $this->getScripts($xpath, $baseDomain),
            $this->getFavicons($xpath, $baseDomain),
            $this->getMetaMedia($xpath, $baseDomain)
        );

        $outputHtml = $document->saveHTML();

        if ($outputHtml === false) {
            throw new HtmlParsingException("Could not generate updated HTML output");
        }

        $sanitizedAssetUrls = array_map('urldecode', $assetUrls);
        $uniqueAssetUrls = array_unique($sanitizedAssetUrls);

        return new HtmlParseResult($outputHtml, $uniqueAssetUrls);
    }

    private function deleteAnchorHrefs(\DOMXPath $xpath): void
    {
        foreach ($xpath->query('//a') as $anchor) {
            if ($anchor->hasAttribute('href')) {
                $anchor->setAttribute('href', '#');
            }
        }
    }

    private function deleteSrcSets(\DOMXPath $xpath): void
    {
        foreach ($xpath->query('//img | //source') as $anchor) {
            if ($anchor->hasAttribute('srcset')) {
                $anchor->removeAttribute('srcset');
            }
        }
    }

    /**
     * @param \DOMXPath $xpath
     * @param string $baseDomain
     * @return string[]
     */
    private function getMedia(\DOMXPath $xpath, string $baseDomain): array
    {
        $assetUrls = [];

        foreach ($xpath->query('//img | //video') as $media) {
            if ($media->hasAttribute('src')) {
                $assetUrls[] = $this->getOriginalAttributeAndConvertToRelativePath($media, 'src', $baseDomain);
            }
            if ($media->hasAttribute('poster')) {
                $assetUrls[] = $this->getOriginalAttributeAndConvertToRelativePath($media, 'poster', $baseDomain);
            }
        }

        foreach ($xpath->query('//svg/use | //svg/image') as $svg) {
            if ($svg->hasAttribute('href')) {
                $assetUrls[] = $this->getOriginalAttributeAndConvertToRelativePath($svg, 'href', $baseDomain);
            }
            if ($svg->hasAttribute('xlink:href')) {
                $assetUrls[] = $this->getOriginalAttributeAndConvertToRelativePath($svg, 'xlink:href', $baseDomain);
            }
        }

        return $assetUrls;
    }

    /**
     * @param \DOMXPath $xpath
     * @param string $baseDomain
     * @return string[]
     */
    private function getCss(\DOMXPath $xpath, string $baseDomain): array
    {
        $assetUrls = [];

        foreach ($xpath->query('//link') as $link) {
            $isStylesheet = $link->getAttribute('type') == 'text/css'
                || $link->getAttribute('rel') == 'stylesheet';
            if ($isStylesheet && $link->hasAttribute('href')) {
                $assetUrls[] = $this->getOriginalAttributeAndConvertToRelativePath($link, 'href', $baseDomain);
            }
        }

        return $assetUrls;
    }

    /**
     * @param \DOMXPath $xpath
     * @param string $baseDomain
     * @return string[]
     */
    private function getPreload(\DOMXPath $xpath, string $baseDomain): array
    {
        $assetUrls = [];

        foreach ($xpath->query('//link') as $link) {
            if ($link->getAttribute('rel') == 'preload' && $link->hasAttribute('href')) {
                $assetUrls[] = $this->getOriginalAttributeAndConvertToRelativePath($link, 'href', $baseDomain);
            }
        }

        return $assetUrls;
    }

    /**
     * @param \DOMXPath $xpath
     * @param string $baseDomain
     * @return string[]
     */
    private function getFavicons(\DOMXPath $xpath, string $baseDomain): array
    {
        $assetUrls = [];

        foreach ($xpath->query('//link') as $link) {
            if (strpos($link->getAttribute('rel'), 'icon') !== false && $link->hasAttribute('href')) {
                $assetUrls[] = $this->getOriginalAttributeAndConvertToRelativePath($link, 'href', $baseDomain);
            }
        }

        return $assetUrls;
    }

    /**
     * @param \DOMXPath $xpath
     * @param string $baseDomain
     * @return string[]
     */
    private function getMetaMedia(\DOMXPath $xpath, string $baseDomain): array
    {
        $tags = [
            'og:image',
            'og:image:url',
            'twitter:image'
        ];

        $assetUrls = [];

        foreach ($xpath->query('//meta') as $meta) {
            $isMetaMediaTag = (
                in_array($meta->getAttribute('property'), $tags, TRUE)
                || in_array($meta->getAttribute('name'), $tags, TRUE)
            );

            if ($isMetaMediaTag && $meta->hasAttribute('content')) {
                $assetUrls[] = $this->getOriginalAttributeAndConvertToRelativePath($meta, 'content', $baseDomain);
            }
        }

        return $assetUrls;
    }

    /**
     * @param \DOMXPath $xpath
     * @param string $baseDomain
     * @return string[]
     */
    private function getScripts(\DOMXPath $xpath, string $baseDomain): array
    {
        $assetUrls = [];

        foreach ($xpath->query('//script') as $script) {
            if ($script->hasAttribute('src')) {
                $assetUrls[] = $this->getOriginalAttributeAndConvertToRelativePath($script, 'src', $baseDomain);
            }
        }

        return $assetUrls;
    }

    /**
     * @param \DOMElement $element
     * @param string $attributeName
     * @param string $baseDomain
     * @return string
     */
    private function getOriginalAttributeAndConvertToRelativePath(\DOMElement $element, string $attributeName, string $baseDomain): string
    {
        $originalAttributeValue = $element->getAttribute($attributeName);
        $relativePath = $this->uriConverter->convertUriToOfflinePath($originalAttributeValue, $baseDomain);
        $relativePath = $this->uriConverter->cleanRelativePath($relativePath);
        $element->setAttribute($attributeName, $relativePath);

        return $originalAttributeValue;
    }
}
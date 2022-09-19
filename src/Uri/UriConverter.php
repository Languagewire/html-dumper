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

namespace LanguageWire\HtmlDumper\Uri;

class UriConverter
{
    /**
     * Returns the scheme and domain part from a URL
     * @param string $url
     * @return string
     */
    public function getBaseDomainFromUrl(string $url): string
    {
        $urlData = parse_url($url);

        $scheme   = isset($urlData['scheme']) ? $urlData['scheme'] . '://' : '';
        $host     = $urlData['host'] ?? '';
        $port     = isset($urlData['port']) ? ':' . $urlData['port'] : '';

        return "$scheme$host$port";
    }

    /**
     * Converts either a URL or a path into a path used for storage.
     * If the Uri points to a different domain than the base one, then
     * the host is used as a folder prefix.
     * @param string $assetUri
     * @param string $baseDomain
     * @return string
     */
    public function convertUriToOfflinePath(string $assetUri, string $baseDomain): string
    {
        if (filter_var($assetUri, FILTER_VALIDATE_URL) !== false) {
            $baseHost = parse_url($baseDomain, PHP_URL_HOST);
            $host = parse_url($assetUri, PHP_URL_HOST);
            $prefix = "";

            if ($host != $baseHost) {
                $prefix = sprintf("%s/", $host);
            }

            $path = parse_url($assetUri, PHP_URL_PATH) ?? '';
            $path = ltrim($path, '/');

            return $prefix . $path;
        }

        return ltrim($assetUri, '/');
    }

    /**
     * Converts either a URL or a path into an Url.
     * @param string $assetUri
     * @param string $baseDomain
     * @return string
     */
    public function convertUriToUrl(string $assetUri, string $baseDomain): string
    {
        if (filter_var($assetUri, FILTER_VALIDATE_URL) !== false) {
            return $assetUri;
        }

        $assetUri = str_replace("../", "", $assetUri);
        return $this->joinUrlWithPath($baseDomain, $assetUri);
    }

    /**
     * Removes query parameters from a path
     * @param string $relativePath
     * @return string
     */
    public function removeQueryParams(string $relativePath): string
    {
        $relativePath = explode("#", $relativePath)[0];
        $relativePath = explode("?", $relativePath)[0];

        return $relativePath;
    }

    /**
     * Prepend "../" as many times as needed according to $depth, only if these don't exist already
     * @todo rename this method
     * @param string $relativePath
     * @param int $depth
     * @return string
     */
    public function prependParentDirectoryDoubleDots(string $relativePath, int $depth): string
    {
        $prefix = str_repeat("../", $depth);

        return $this->joinPaths($prefix, $relativePath);
    }

    /**
     * Counts how many levels deep is a given path
     * @param string $relativePath
     * @return int
     */
    public function countDepthLevelOfPath(string $relativePath): int
    {
        $relativePath = ltrim($relativePath, '/');

        return substr_count($relativePath, '/');
    }

    /**
     * Joins two paths together
     * @param string $path1
     * @param string $path2
     * @return string
     */
    public function joinPaths(string $path1, string $path2): string
    {
        if (empty($path1)) {
            return $path2;
        }
        if (empty($path2)) {
            return $path1;
        }

        $path1 = rtrim($path1, '/');
        $path2 = ltrim($path2, '/');

        return $path1 . '/' . $path2;
    }

    /**
     * Joins an URL and a path together
     * @param string $url
     * @param string $path
     * @return string
     */
    public function joinUrlWithPath(string $url, string $path): string
    {
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }
}

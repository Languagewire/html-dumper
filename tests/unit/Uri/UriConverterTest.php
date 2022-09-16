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

namespace unit\LanguageWire\Uri;

use LanguageWire\HtmlDumper\Uri\UriConverter;
use PHPUnit\Framework\TestCase;

class UriConverterTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideUrlToDomain
     */
    public function getBaseDomainFromUrl__WHEN_a_fully_qualified_URL_is_provided_THEN_a_domain_is_returned(string $url, string $expectedResult): void
    {
        $converter = new UriConverter();

        $this->assertEquals($expectedResult, $converter->getBaseDomainFromUrl($url));
    }

    /**
     * @test
     * @dataProvider provideUriToPath
     */
    public function convertUriToOfflinePath__WHEN_a_URI_is_provided_THEN_a_path_is_returned(string $uri, string $expectedResult): void
    {
        $converter = new UriConverter();
        $baseDomain = "http://example.com";

        $this->assertEquals($expectedResult, $converter->convertUriToOfflinePath($uri, $baseDomain));
    }

    /**
     * @test
     * @dataProvider provideUriToUrl
     */
    public function convertUriToUrl__WHEN_a_URI_is_provided_THEN_an_url_is_returned(string $uri, string $expectedResult): void
    {
        $converter = new UriConverter();
        $baseDomain = "http://example.com";

        $this->assertEquals($expectedResult, $converter->convertUriToUrl($uri, $baseDomain));
    }

    /**
     * @test
     * @dataProvider providePathsToJoin
     */
    public function joinPaths__WHEN_two_paths_are_provided_THEN_a_path_is_returned(string $path1, string $path2, string $expectedResult): void
    {
        $converter = new UriConverter();

        $this->assertEquals($expectedResult, $converter->joinPaths($path1, $path2));
    }

    /**
     * @test
     * @dataProvider provideUrlAndPathToPath
     */
    public function joinPaths__WHEN_a_url_and_a_path_are_provided_THEN_a_url_is_returned(string $url, string $path, string $expectedResult): void
    {
        $converter = new UriConverter();

        $this->assertEquals($expectedResult, $converter->joinUrlWithPath($url, $path));
    }

    /**
     * @test
     */
    public function cleanRelativePath__WHEN_a_path_has_extra_parts_THEN_they_are_removed(): void
    {
        $converter = new UriConverter();

        $this->assertEquals("file.png", $converter->cleanRelativePath("file.png#hash"));
        $this->assertEquals("file.png", $converter->cleanRelativePath("file.png?query=param"));
    }

    /**
     * @test
     */
    public function prependParentDirectoryDoubleDots__WHEN_depth_is_greater_than_zero_THEN_result_has_parent_dir_prefix(): void
    {
        $converter = new UriConverter();

        $this->assertEquals("../../file.png", $converter->prependParentDirectoryDoubleDots("file.png", 2));
    }

    /**
     * @test
     */
    public function prependParentDirectoryDoubleDots__WHEN_depth_is_zero_THEN_result_is_unchanged(): void
    {
        $converter = new UriConverter();

        $this->assertEquals("file.png", $converter->prependParentDirectoryDoubleDots("file.png", 0));
    }

    /**
     * @test
     * @dataProvider providePathsToCountDepth
     */
    public function countDepthLevelOfPath__WHEN_a_path_is_provided_THEN_the_correct_depth_level_is_returned(string $path, int $expectedDepth): void
    {
        $converter = new UriConverter();

        $this->assertEquals($expectedDepth, $converter->countDepthLevelOfPath($path));
    }

    public function provideUrlToDomain(): array
    {
        return [
            ['http://example.com/file.png', 'http://example.com'],
            ['http://example.com/folder/file.png', 'http://example.com'],
            ['http://example.com/folder/file.png?query=parameter', 'http://example.com'],
            ['http://example.com/folder/file.png?query=parameter&second', 'http://example.com'],
            ['http://example.com/folder/file.png?query=parameter&second#hash', 'http://example.com'],
            ['http://example.com/path', 'http://example.com'],
            ['http://example.com/path/subfolder', 'http://example.com'],
            ['http://example.com/', 'http://example.com'],
            ['http://example.com', 'http://example.com'],
            ['http://example.com', 'http://example.com'],
        ];
    }

    public function provideUriToPath(): array
    {
        return [
            ['http://example.com/file.png', 'file.png'],
            ['http://example.com/folder/file.png', 'folder/file.png'],
            ['http://example.com/folder/file.png?query=parameter', 'folder/file.png'],
            ['http://example.com/folder/file.png?query=parameter&second', 'folder/file.png'],
            ['http://example.com/folder/file.png?query=parameter&second#hash', 'folder/file.png'],
            ['http://example.com/path', 'path'],
            ['http://example.com/path/subfolder', 'path/subfolder'],
            ['http://example.com/', ''],
            ['http://example.com', ''],
            ['http://example.com', ''],

            ['/absolute/path.png', 'absolute/path.png'],
            ['relative/path.png', 'relative/path.png'],
            ['path.png', 'path.png'],
            ['/', ''],

            ['http://externaldomain.com', 'externaldomain.com/'],
            ['http://externaldomain.com/', 'externaldomain.com/'],
            ['http://externaldomain.com/file.png', 'externaldomain.com/file.png'],
            ['http://externaldomain.com/folder/file.png', 'externaldomain.com/folder/file.png'],
        ];
    }

    public function provideUriToUrl(): array
    {
        return [
            ['http://example.com/file.png', 'http://example.com/file.png'],
            ['http://example.com/folder/file.png', 'http://example.com/folder/file.png'],
            ['http://example.com/folder/file.png?query=parameter', 'http://example.com/folder/file.png?query=parameter'],
            ['http://example.com/folder/file.png?query=parameter&second#hash', 'http://example.com/folder/file.png?query=parameter&second#hash'],
            ['http://example.com/path', 'http://example.com/path'],
            ['http://example.com/', 'http://example.com/'],
            ['http://example.com', 'http://example.com'],

            ['/absolute/path.png', 'http://example.com/absolute/path.png'],
            ['relative/path.png', 'http://example.com/relative/path.png'],
            ['path.png', 'http://example.com/path.png'],
            ['/', 'http://example.com/'],

            ['http://externaldomain.com', 'http://externaldomain.com'],
            ['http://externaldomain.com/', 'http://externaldomain.com/'],
            ['http://externaldomain.com/file.png', 'http://externaldomain.com/file.png'],
            ['http://externaldomain.com/folder/file.png', 'http://externaldomain.com/folder/file.png'],

            ['../absolute/path.png', 'http://example.com/absolute/path.png'],
            ['../../absolute/path.png', 'http://example.com/absolute/path.png'],
        ];
    }

    public function providePathsToJoin(): array
    {
        return [
            ['/folder', 'file.png', '/folder/file.png'],
            ['/folder', '/file.png', '/folder/file.png'],
            ['/folder/', 'file.png', '/folder/file.png'],
            ['/folder/', '/file.png', '/folder/file.png'],
            ['/folder', 'subfolder/file.png', '/folder/subfolder/file.png'],
            ['/folder', '/subfolder/file.png', '/folder/subfolder/file.png'],
            ['/folder/', 'subfolder/file.png', '/folder/subfolder/file.png'],
            ['/folder/', '/subfolder/file.png', '/folder/subfolder/file.png'],
            ['/', 'file.png', '/file.png'],
            ['/', '/file.png', '/file.png'],
            ['/', 'subfolder/file.png', '/subfolder/file.png'],
            ['/', '/subfolder/file.png', '/subfolder/file.png'],

            ['', '/file.png', '/file.png'],
            ['', 'file.png', 'file.png'],
            ['/folder', '', '/folder'],
            ['folder', '', 'folder'],
        ];
    }

    public function provideUrlAndPathToPath(): array
    {
        return [
            ['http://example.com', '/file.png', 'http://example.com/file.png'],
            ['http://example.com/', '/file.png', 'http://example.com/file.png'],
            ['http://example.com/', 'file.png', 'http://example.com/file.png'],
            ['http://example.com', 'file.png', 'http://example.com/file.png'],
            ['http://example.com', '/folder/file.png', 'http://example.com/folder/file.png'],
            ['http://example.com/', '/folder/file.png', 'http://example.com/folder/file.png'],
            ['http://example.com/', 'folder/file.png', 'http://example.com/folder/file.png'],
            ['http://example.com', 'folder/file.png', 'http://example.com/folder/file.png'],
        ];
    }

    public function providePathsToCountDepth(): array
    {
        return [
            ['', 0],
            ['/', 0],
            ['file.png', 0],
            ['/file.png', 0],
            ['folder/file.png', 1],
            ['/folder/file.png', 1],
            ['folder/subfolder/file.png', 2],
            ['/folder/subfolder/file.png', 2],
        ];
    }
}

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

namespace unit\LanguageWire\Service;

use builder\LanguageWire\ResponseBuilder;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use LanguageWire\HtmlDumper\IO\Filesystem;
use LanguageWire\HtmlDumper\Parser\CssParser;
use LanguageWire\HtmlDumper\Parser\ParseResult;
use LanguageWire\HtmlDumper\Service\AssetDownloader;
use LanguageWire\HtmlDumper\Uri\UriConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use traits\LanguageWire\TemporaryFileTestsTrait;

class AssetDownloaderTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    use TemporaryFileTestsTrait;

    private const TEMP_TARGET_DIRECTORY = "asset-downloader-target";
    private const BASE_DOMAIN = "https://example.com";

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $httpClient;
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $filesystem;
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $uriConverter;
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $cssParser;

    protected function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->uriConverter = $this->prophesize(UriConverter::class);

        $this->cssParser = $this->prophesize(CssParser::class);
    }

    /**
     * @test
     */
    public function downloadAssets__WHEN_assets_and_a_target_directory_are_provided_THEN_files_are_created(): void
    {
        $baseDomain = self::BASE_DOMAIN;
        $targetDirectory = self::TEMP_TARGET_DIRECTORY;

        $assetPaths = [
            '/data/img/screenshot1.png',
            '/data/img/screenshot2.png'
        ];

        $this->uriConverterWillHandleAssets($assetPaths, $targetDirectory, $baseDomain);
        $this->httpRequestsForAssetsShouldBeMade($assetPaths, $baseDomain);

        $this->httpClientWillReturnEmptyContentAssets($assetPaths, $baseDomain);

        $pageDownloader = $this->assetDownloader();

        $pageDownloader->downloadAssets($assetPaths, $targetDirectory, $baseDomain);

        $this->filesystem->createFile("$targetDirectory/data/img/screenshot1.png", Argument::type("string"), true)->shouldHaveBeenCalled();
        $this->filesystem->createFile("$targetDirectory/data/img/screenshot2.png", Argument::type("string"), true)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function downloadAssets__WHEN_some_assets_cannot_be_downloaded_THEN_only_some_files_are_created(): void
    {
        $baseDomain = self::BASE_DOMAIN;
        $targetDirectory = self::TEMP_TARGET_DIRECTORY;

        $existingAssetPaths = [
            '/data/img/screenshot1.png'
        ];

        $nonExistingAssetPaths = [
            '/data/img/screenshot2.png'
        ];

        $this->uriConverterWillHandleAssets($existingAssetPaths, $targetDirectory, $baseDomain);
        $this->uriConverterWillHandleAssets($nonExistingAssetPaths, $targetDirectory, $baseDomain);

        $this->httpRequestsForAssetsShouldBeMade($existingAssetPaths, $baseDomain);
        $this->httpRequestsForAssetsShouldBeMade($nonExistingAssetPaths, $baseDomain);

        $this->httpClientWillReturnEmptyContentAssets($existingAssetPaths, $baseDomain);
        $this->httpClientWillThrowExceptionOnAssets($nonExistingAssetPaths, $baseDomain);

        $pageDownloader = $this->assetDownloader();

        $pageDownloader->downloadAssets(array_merge($existingAssetPaths, $nonExistingAssetPaths), $targetDirectory, $baseDomain);

        $this->filesystem
            ->createFile("$targetDirectory/data/img/screenshot1.png", Argument::type("string"), true)
            ->shouldHaveBeenCalled();

        $this->filesystem
            ->createFile("$targetDirectory/data/img/screenshot2.png", Argument::type("string"), true)
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function downloadAssets__WHEN_some_assets_dont_have_extensions_THEN_they_are_ignored(): void
    {
        $baseDomain = self::BASE_DOMAIN;
        $targetDirectory = self::TEMP_TARGET_DIRECTORY;

        $existingAssetPaths = [
            '/data/img/screenshot1.png'
        ];

        $assetPathsWithoutExtension = [
            '/data/img/screenshot2_no_extension',
            '/data/img/screenshot3_no_extension'
        ];

        $this->uriConverterWillHandleAssets($existingAssetPaths, $targetDirectory, $baseDomain);
        $this->uriConverterWillHandleAssets($assetPathsWithoutExtension, $targetDirectory, $baseDomain);

        $this->httpRequestsForAssetsShouldBeMade($existingAssetPaths, $baseDomain);

        $this->httpClientWillReturnEmptyContentAssets($existingAssetPaths, $baseDomain);

        $pageDownloader = $this->assetDownloader();

        $pageDownloader->downloadAssets(array_merge($existingAssetPaths, $assetPathsWithoutExtension), $targetDirectory, $baseDomain);

        $this->filesystem
            ->createFile("$targetDirectory/data/img/screenshot1.png", Argument::type("string"), true)
            ->shouldHaveBeenCalled();

        $this->filesystem
            ->createFile("$targetDirectory/data/img/screenshot2_no_extension", Argument::type("string"), true)
            ->shouldNotHaveBeenCalled();
        $this->filesystem
            ->createFile("$targetDirectory/data/img/screenshot3_no_extension", Argument::type("string"), true)
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function downloadAssets__WHEN_assets_have_stylesheets_without_assets_THEN_no_extra_files_are_created(): void
    {
        $baseDomain = self::BASE_DOMAIN;
        $targetDirectory = self::TEMP_TARGET_DIRECTORY;

        $assetPaths = [
            '/data/img/screenshot1.png',
            '/data/img/screenshot2.png',
            '/data/css/style.css',
        ];

        $this->filesystem->getParentDirectory("$targetDirectory/data/css/style.css")->willReturn("$targetDirectory/data/css/");

        $this->uriConverterWillHandleAssets($assetPaths, $targetDirectory, $baseDomain);
        $this->httpRequestsForAssetsShouldBeMade($assetPaths, $baseDomain);

        $this->httpClientWillReturnEmptyContentAssets($assetPaths, $baseDomain);

        $this->filesystem
            ->readFile("$targetDirectory/data/css/style.css")
            ->willReturn("");

        $this->uriConverter
            ->countDepthLevelOfPath("/data/css/style.css")
            ->willReturn(2);

        $this->cssParser->parseCssContent("", "$targetDirectory/data/css/", $baseDomain)->willReturn(
            new ParseResult("", [])
        );

        $pageDownloader = $this->assetDownloader();

        $pageDownloader->downloadAssets($assetPaths, $targetDirectory, $baseDomain);

        $this->filesystem->createFile("$targetDirectory/data/img/screenshot1.png", Argument::type("string"), true)->shouldHaveBeenCalled();
        $this->filesystem->createFile("$targetDirectory/data/img/screenshot2.png", Argument::type("string"), true)->shouldHaveBeenCalled();
        $this->filesystem->createFile("$targetDirectory/data/css/style.css", Argument::type("string"), true)->shouldHaveBeenCalled();

        $this->filesystem->writeToFile("$targetDirectory/data/css/style.css", Argument::type("string"))->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function downloadAssets__WHEN_assets_have_stylesheets_with_assets_THEN_extra_files_are_created(): void
    {
        $baseDomain = self::BASE_DOMAIN;
        $targetDirectory = self::TEMP_TARGET_DIRECTORY;

        $assetPaths = [
            '/data/img/screenshot1.png',
            '/data/img/screenshot2.png',
            '/data/css/style.css',
        ];

        $this->filesystem->getParentDirectory("$targetDirectory/data/css/style.css")->willReturn("$targetDirectory/data/css/");

        $cssRealAssetPaths = [
            '/data/img/background.png',
            '/data/css/fonts/font.woff'
        ];

        $this->uriConverterWillHandleAssets($assetPaths, $targetDirectory, $baseDomain);
        $this->uriConverterWillHandleAssets($cssRealAssetPaths, $targetDirectory, $baseDomain);

        $this->httpRequestsForAssetsShouldBeMade($assetPaths, $baseDomain);
        $this->httpRequestsForAssetsShouldBeMade($cssRealAssetPaths, $baseDomain);

        $this->httpClientWillReturnEmptyContentAssets($assetPaths, $baseDomain);
        $this->httpClientWillReturnEmptyContentAssets($cssRealAssetPaths, $baseDomain);

        $this->filesystem
            ->readFile("$targetDirectory/data/css/style.css")
            ->willReturn("");

        $this->uriConverter
            ->countDepthLevelOfPath("/data/css/style.css")
            ->willReturn(2);

        $this->cssParser->parseCssContent("", "$targetDirectory/data/css/", $baseDomain)->willReturn(
            new ParseResult("", $cssRealAssetPaths)
        );

        $pageDownloader = $this->assetDownloader();

        $pageDownloader->downloadAssets($assetPaths, $targetDirectory, $baseDomain);

        $this->filesystem->createFile("$targetDirectory/data/img/screenshot1.png", Argument::type("string"), true)->shouldHaveBeenCalled();
        $this->filesystem->createFile("$targetDirectory/data/img/screenshot2.png", Argument::type("string"), true)->shouldHaveBeenCalled();
        $this->filesystem->createFile("$targetDirectory/data/css/style.css", Argument::type("string"), true)->shouldHaveBeenCalled();

        $this->filesystem->writeToFile("$targetDirectory/data/css/style.css", Argument::type("string"))->shouldHaveBeenCalled();
        $this->filesystem->createFile("$targetDirectory/data/img/background.png", Argument::type("string"), true)->shouldHaveBeenCalled();
        $this->filesystem->createFile("$targetDirectory/data/css/fonts/font.woff", Argument::type("string"), true)->shouldHaveBeenCalled();
    }

    private function assetDownloader(): AssetDownloader
    {
        return new AssetDownloader(
            $this->httpClient->reveal(),
            $this->filesystem->reveal(),
            $this->uriConverter->reveal(),
            $this->cssParser->reveal()
        );
    }

    private function httpClientWillThrowExceptionOnUrl(string $url): void
    {
        $exception = $this->prophesize(ServerException::class)->reveal();
        $this->httpClient->request('GET', $url)->willThrow($exception);
    }

    private function httpClientWillReturnEmptyContentAssets(array $expectedAssetPaths, string $baseDomain): void
    {
        foreach ($expectedAssetPaths as $expectedAssetUrl) {
            $this->httpClient->request('GET', $baseDomain . $expectedAssetUrl)->willReturn((new ResponseBuilder())->build());
        }
    }

    private function httpClientWillThrowExceptionOnAssets(array $expectedAssetPaths, string $baseDomain): void
    {
        foreach ($expectedAssetPaths as $expectedAssetUrl) {
            $this->httpClientWillThrowExceptionOnUrl($baseDomain . $expectedAssetUrl);
        }
    }

    private function httpRequestsForAssetsShouldBeMade(array $expectedAssetPaths, string $baseDomain): void
    {
        foreach ($expectedAssetPaths as $expectedAssetUrl) {
            $this->httpClient->request('GET', $baseDomain . $expectedAssetUrl)->shouldBeCalled();
        }
    }

    private function uriConverterWillHandleAssets(array $assetPaths, string $targetDirectory, string $baseDomain): void
    {
        foreach ($assetPaths as $assetPath) {
            $assetUrl = $baseDomain . $assetPath;

            $this->uriConverter->convertUriToOfflinePath($assetPath, $baseDomain)->willReturn($assetPath);
            $this->uriConverter->convertUriToUrl($assetPath, $baseDomain)->willReturn($assetUrl);

            $this->uriConverter
                ->convertAssertUrlToLocalPath($assetUrl, $baseDomain, $targetDirectory)
                ->willReturn($targetDirectory . $assetPath);
        }
    }
}

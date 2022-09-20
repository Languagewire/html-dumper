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
use data\LanguageWire\CssFileProvider;
use data\LanguageWire\HtmlFileProvider;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ServerException;
use LanguageWire\HtmlDumper\IO\Filesystem;
use LanguageWire\HtmlDumper\Parser\CssParser;
use LanguageWire\HtmlDumper\Parser\HtmlParser;
use LanguageWire\HtmlDumper\Parser\ParseResult;
use LanguageWire\HtmlDumper\Service\AssetDownloader;
use LanguageWire\HtmlDumper\Service\PageDownloader;
use LanguageWire\HtmlDumper\Uri\UriConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
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

        $this->uriConverter->getBaseDomainFromUrl(Argument::type('string'))->willReturn(self::BASE_DOMAIN);
        $this->uriConverter->removeQueryParams(Argument::type('string'))->willReturnArgument(0);

        $this->htmlParser = $this->prophesize(HtmlParser::class);
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

        $this->httpClientWillReturnAssets($assetPaths, $baseDomain);

        $pageDownloader = $this->assetDownloader();

        $pageDownloader->downloadAssets($assetPaths, $targetDirectory, $baseDomain);

        $this->filesystem->createFile("$targetDirectory/data/img/screenshot1.png", Argument::type(StreamInterface::class), true)->shouldHaveBeenCalled();
        $this->filesystem->createFile("$targetDirectory/data/img/screenshot2.png", Argument::type(StreamInterface::class), true)->shouldHaveBeenCalled();
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

        $this->httpClientWillReturnAssets($existingAssetPaths, $baseDomain);
        $this->httpClientWillThrowExceptionOnAssets($nonExistingAssetPaths, $baseDomain);

        $pageDownloader = $this->assetDownloader();

        $pageDownloader->downloadAssets(array_merge($existingAssetPaths, $nonExistingAssetPaths), $targetDirectory, $baseDomain);

        $this->filesystem
            ->createFile("$targetDirectory/data/img/screenshot1.png", Argument::type(StreamInterface::class), true)
            ->shouldHaveBeenCalled();

        $this->filesystem
            ->createFile("$targetDirectory/data/img/screenshot2.png", Argument::type(StreamInterface::class), true)
            ->shouldNotHaveBeenCalled();
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

    private function httpClientWillReturnBasicHtmlPage(string $baseDomain): void
    {
        $htmlBody = (new HtmlFileProvider())->getValidHtmlWithAssets();
        $response = (new ResponseBuilder())->withBodyString($htmlBody)->build();
        $this->httpClient->request('GET', $baseDomain)->willReturn($response);
    }

    private function httpClientWillThrowExceptionOnUrl(string $url): void
    {
        $exception = $this->prophesize(ServerException::class)->reveal();
        $this->httpClient->request('GET', $url)->willThrow($exception);
    }

    private function httpClientWillReturnAssets(array $expectedAssetPaths, string $baseDomain): void
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
            $this->uriConverter->convertUriToOfflinePath($assetUrl, $baseDomain)->willReturn($assetPath);

            $this->uriConverter->convertUriToUrl($assetPath, $baseDomain)->willReturn($assetUrl);

            $this->uriConverter->removeQueryParams($assetPath)->willReturn($assetPath);
            $this->uriConverter->joinPaths($targetDirectory, $assetPath)->willReturn($targetDirectory . $assetPath);
        }
    }

    /**
     * @param array $expectedAssetPaths
     * @param string $baseDomain
     * @return void
     */
    private function htmlParserReturnsResult(array $expectedAssetPaths, string $baseDomain): void
    {
        $htmlBody = (new HtmlFileProvider())->getValidHtmlWithAssets();
        $parseResult = new ParseResult($htmlBody, $expectedAssetPaths);
        $this->htmlParser->parseHtmlContent(Argument::type("string"), $baseDomain)->willReturn($parseResult);
    }

    /**
     * @param array $expectedAssetPaths
     * @param string $baseDomain
     * @return void
     */
    private function cssParserReturnsResult(array $expectedAssetPaths, string $baseDomain): void
    {
        $cssBody = (new CssFileProvider())->getValidCssWithAssets();
        $parseResult = new ParseResult($cssBody, $expectedAssetPaths);
        $this->cssParser->parseCssContent(Argument::type("string"), $baseDomain, 2)->willReturn($parseResult);
    }
}

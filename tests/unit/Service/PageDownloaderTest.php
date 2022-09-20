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
use data\LanguageWire\HtmlFileProvider;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use LanguageWire\HtmlDumper\IO\Filesystem;
use LanguageWire\HtmlDumper\Parser\HtmlParser;
use LanguageWire\HtmlDumper\Parser\ParseResult;
use LanguageWire\HtmlDumper\Service\AssetDownloader;
use LanguageWire\HtmlDumper\Service\PageDownloader;
use LanguageWire\HtmlDumper\Uri\UriConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\StreamInterface;
use traits\LanguageWire\TemporaryFileTestsTrait;

class PageDownloaderTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    private const TEMP_TARGET_DIRECTORY = "page-downloader-target";
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
    private $htmlParser;
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $assetDownloader;

    protected function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->uriConverter = $this->prophesize(UriConverter::class);

        $this->uriConverter->getBaseDomainFromUrl(Argument::type('string'))->willReturn(self::BASE_DOMAIN);

        $this->htmlParser = $this->prophesize(HtmlParser::class);
        $this->assetDownloader = $this->prophesize(AssetDownloader::class);
    }

    /**
     * @test
     */
    public function downloadPage__WHEN_an_url_and_target_directory_are_provided_THEN_files_are_created(): void
    {
        $baseDomain = self::BASE_DOMAIN;
        $targetDirectory = self::TEMP_TARGET_DIRECTORY;

        $expectedAssetPaths = [
            '/data/img/screenshot1.png',
            '/data/img/screenshot2.png'
        ];

        $this->uriConverter->joinPaths($targetDirectory, '/index.html')->willReturn("$targetDirectory/index.html");

        $this->httpClientWillReturnBasicHtmlPage($baseDomain);
        $this->htmlParserReturnsResult($expectedAssetPaths, $baseDomain);

        $pageDownloader = $this->pageDownloader();

        $result = $pageDownloader->download($baseDomain, $targetDirectory);

        $this->assertTrue($result);

        $this->filesystem->createParentDirectory("$targetDirectory/index.html")->shouldHaveBeenCalled();
        $this->filesystem->createFile("$targetDirectory/index.html", Argument::type(StreamInterface::class))->shouldHaveBeenCalled();

        $this->assetDownloader->downloadAssets($expectedAssetPaths, $targetDirectory, $baseDomain)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function downloadPage__WHEN_base_url_cannot_be_reached_THEN_result_is_false(): void
    {
        $baseDomain = self::BASE_DOMAIN;
        $targetDirectory = self::TEMP_TARGET_DIRECTORY;

        $this->uriConverter->getBaseDomainFromUrl($baseDomain)->willReturn($baseDomain);

        $this->httpClientWillThrowExceptionOnUrl($baseDomain);

        $pageDownloader = $this->pageDownloader();

        $result = $pageDownloader->download($baseDomain, $targetDirectory);

        $this->assertFalse($result);
    }

    private function pageDownloader(): PageDownloader
    {
        return new PageDownloader(
            $this->httpClient->reveal(),
            $this->filesystem->reveal(),
            $this->uriConverter->reveal(),
            $this->htmlParser->reveal(),
            $this->assetDownloader->reveal()
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
}

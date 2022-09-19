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
use LanguageWire\HtmlDumper\Parser\CssParser;
use LanguageWire\HtmlDumper\Parser\HtmlParser;
use LanguageWire\HtmlDumper\Parser\ParseResult;
use LanguageWire\HtmlDumper\Service\PageDownloader;
use LanguageWire\HtmlDumper\Uri\UriConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use traits\LanguageWire\TemporaryFileTestsTrait;

class PageDownloaderTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    use TemporaryFileTestsTrait;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $httpClient;
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
    private $cssParser;

    protected function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);

        $this->uriConverter = $this->prophesize(UriConverter::class);

        $this->uriConverter->getBaseDomainFromUrl(Argument::type('string'))->willReturn('https://example.com');
        $this->uriConverter->removeQueryParams(Argument::type('string'))->willReturnArgument(0);

        $this->htmlParser = $this->prophesize(HtmlParser::class);
        $this->cssParser = $this->prophesize(CssParser::class);

        $this->createTemporaryDirectory("/tmp/target");
    }

    protected function tearDown(): void
    {
        if (is_dir("/tmp/target")) {
            $this->recursivelyDeleteDirectory("/tmp/target");
        }
    }

    /**
     * @test
     */
    public function downloadPage__WHEN_an_url_and_target_directory_are_provided_THEN_files_are_created(): void
    {
        $baseDomain = "https://example.com";
        $targetDirectory = "/tmp/target";

        $expectedAssetPaths = [
            '/data/img/screenshot1.png',
            '/data/img/screenshot2.png'
        ];

        $this->uriConverterWillHandleAssets($expectedAssetPaths, $targetDirectory, $baseDomain);
        $this->httpClientWillReturnBasicHtmlPage($baseDomain);
        $this->httpClientWillReturnAssets($expectedAssetPaths, $targetDirectory, $baseDomain);
        $this->httpRequestsForAssetsWillBeMade($expectedAssetPaths, $baseDomain);
        $this->htmlParserReturnsResult($expectedAssetPaths, $baseDomain);

        $pageDownloader = $this->pageDownloader();

        $result = $pageDownloader->download($baseDomain, $targetDirectory);

        $this->assertTrue($result);
        $this->assertDirectoryExists($targetDirectory);
        $this->assertFileExists("$targetDirectory/index.html");
        $this->assertFileExists("$targetDirectory/data/img/screenshot1.png");
        $this->assertFileExists("$targetDirectory/data/img/screenshot2.png");
    }

    /**
     * @test
     */
    public function downloadPage__WHEN_an_page_has_css_assets_THEN_assets_within_it_are_downloaded(): void
    {
        $baseDomain = "https://example.com";
        $targetDirectory = "/tmp/target";

        $expectedAssetPaths = [
            '/data/css/style.css',
            '/data/img/screenshot1.png',
            '/data/img/screenshot2.png'
        ];

        $this->uriConverterWillHandleAssets($expectedAssetPaths, $targetDirectory, $baseDomain);
        $this->httpClientWillReturnBasicHtmlPage($baseDomain);
        $this->httpClientWillReturnAssets($expectedAssetPaths, $targetDirectory, $baseDomain);
        $this->httpRequestsForAssetsWillBeMade($expectedAssetPaths, $baseDomain);
        $this->htmlParserReturnsResult($expectedAssetPaths, $baseDomain);

        $expectedCssAssetPaths = [
            '/data/img/background.png'
        ];

        $this->uriConverter->countDepthLevelOfPath(Argument::type('string'))->willReturn(2);
        $this->uriConverter->prependParentDirectoryDoubleDots('data/img/background.png', 2)->willReturn('../../data/img/background.png');

        $this->uriConverterWillHandleAssets($expectedCssAssetPaths, $targetDirectory, $baseDomain);
        $this->httpClientWillReturnAssets($expectedCssAssetPaths, $targetDirectory, $baseDomain);
        $this->httpRequestsForAssetsWillBeMade($expectedCssAssetPaths, $baseDomain);
        $this->cssParserReturnsResult($expectedCssAssetPaths, $baseDomain);

        $pageDownloader = $this->pageDownloader();

        $result = $pageDownloader->download($baseDomain, $targetDirectory);

        $this->assertTrue($result);
        $this->assertDirectoryExists($targetDirectory);
        $this->assertFileExists("$targetDirectory/index.html");
        $this->assertFileExists("$targetDirectory/data/img/screenshot1.png");
        $this->assertFileExists("$targetDirectory/data/img/screenshot2.png");
        $this->assertFileExists("$targetDirectory/data/img/background.png");
    }

    /**
     * @test
     */
    public function downloadPage__WHEN_some_assets_dont_have_file_extensions_THEN_those_files_are_not_created(): void
    {
        $baseDomain = "https://example.com";
        $targetDirectory = "/tmp/target";

        $expectedValidAssetPaths = [
            '/data/img/screenshot1.png',
            '/data/img/screenshot2.png'
        ];
        $expectedInvalidAssetPaths = [
            '/data/img/unknownextension',
            '/',
        ];

        $expectedAssetPaths = array_merge($expectedValidAssetPaths, $expectedInvalidAssetPaths);

        $this->uriConverterWillHandleAssets($expectedAssetPaths, $targetDirectory, $baseDomain);
        $this->httpClientWillReturnBasicHtmlPage($baseDomain);

        $this->httpClientWillReturnAssets($expectedValidAssetPaths, $targetDirectory, $baseDomain);

        $this->httpRequestsForAssetsWillBeMade($expectedValidAssetPaths, $baseDomain);
        $this->httpRequestsForAssetsWillNotBeMade($expectedInvalidAssetPaths, $baseDomain);

        $this->htmlParserReturnsResult($expectedAssetPaths, $baseDomain);

        $pageDownloader = $this->pageDownloader();

        $result = $pageDownloader->download($baseDomain, $targetDirectory);

        $this->assertTrue($result);
        $this->assertDirectoryExists($targetDirectory);
        $this->assertFileExists("$targetDirectory/index.html");
        $this->assertFileExists("$targetDirectory/data/img/screenshot1.png");
        $this->assertFileExists("$targetDirectory/data/img/screenshot2.png");
        $this->assertFileDoesNotExist("$targetDirectory/data/img/unknownextension");
    }

    /**
     * @test
     */
    public function downloadPage__WHEN_some_assets_throw_exceptions_THEN_those_files_are_not_created(): void
    {
        $baseDomain = "https://example.com";
        $targetDirectory = "/tmp/target";

        $expectedExistingAssetPaths = [
            '/data/img/screenshot1.png',
            '/data/img/screenshot2.png'
        ];
        $expectedNonExistingAssetPaths = [
            '/data/img/404.png'
        ];

        $expectedAssetPaths = array_merge($expectedExistingAssetPaths, $expectedNonExistingAssetPaths);

        $this->uriConverterWillHandleAssets($expectedAssetPaths, $targetDirectory, $baseDomain);
        $this->httpClientWillReturnBasicHtmlPage($baseDomain);

        $this->httpClientWillReturnAssets($expectedExistingAssetPaths, $targetDirectory, $baseDomain);
        $this->httpClientWillNotReturnAssets($expectedNonExistingAssetPaths, $baseDomain);

        $this->httpRequestsForAssetsWillBeMade($expectedAssetPaths, $baseDomain);

        $this->htmlParserReturnsResult($expectedAssetPaths, $baseDomain);

        $pageDownloader = $this->pageDownloader();

        $result = $pageDownloader->download($baseDomain, $targetDirectory);

        $this->assertTrue($result);
        $this->assertDirectoryExists($targetDirectory);
        $this->assertFileExists("$targetDirectory/index.html");
        $this->assertFileExists("$targetDirectory/data/img/screenshot1.png");
        $this->assertFileExists("$targetDirectory/data/img/screenshot2.png");
        $this->assertFileDoesNotExist("$targetDirectory/data/img/404.png");
    }

    /**
     * @test
     */
    public function downloadPage__WHEN_target_directory_exists_THEN_exception_is_thrown(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $url = "https://example.com";
        $targetDirectory = "/tmp/target";

        mkdir($targetDirectory);

        $pageDownloader = $this->pageDownloader();
        $pageDownloader->download($url, $targetDirectory);

        rmdir($targetDirectory);
    }

    /**
     * @test
     */
    public function downloadPage__WHEN_client_returns_null_THEN_false_is_returned(): void
    {
        $url = "https://example.com";
        $targetDirectory = "/tmp/target";

        $this->httpClientWillThrowExceptionOnUrl($url);
        $pageDownloader = $this->pageDownloader();
        $result = $pageDownloader->download($url, $targetDirectory);

        $this->assertFalse($result);
    }

    private function pageDownloader(): PageDownloader
    {
        return new PageDownloader(
            $this->httpClient->reveal(),
            $this->uriConverter->reveal(),
            $this->htmlParser->reveal(),
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

    private function httpClientWillReturnAssets(array $expectedAssetPaths, string $targetDirectory, string $baseDomain): void
    {
        $this->uriConverter->joinPaths($targetDirectory, '/index.html')->willReturn($targetDirectory . '/index.html');

        $assetResponse = (new ResponseBuilder())->build();
        foreach ($expectedAssetPaths as $expectedAssetUrl) {
            $this->httpClient->request('GET', $baseDomain . $expectedAssetUrl)->willReturn($assetResponse);
        }
    }

    private function httpClientWillNotReturnAssets(array $expectedAssetPaths, string $baseDomain): void
    {
        foreach ($expectedAssetPaths as $expectedAssetUrl) {
            $this->httpClientWillThrowExceptionOnUrl($baseDomain . $expectedAssetUrl);
        }
    }

    private function httpRequestsForAssetsWillBeMade(array $expectedAssetPaths, string $baseDomain): void
    {
        foreach ($expectedAssetPaths as $expectedAssetUrl) {
            $this->httpClient->request('GET', $baseDomain . $expectedAssetUrl)->shouldBeCalled();
        }
    }

    private function httpRequestsForAssetsWillNotBeMade(array $expectedAssetPaths, string $baseDomain): void
    {
        foreach ($expectedAssetPaths as $expectedAssetUrl) {
            $this->httpClient->request('GET', $baseDomain . $expectedAssetUrl)->shouldNotBeCalled();
        }
    }

    private function uriConverterWillHandleAssets(array $expectedAssetPaths, string $targetDirectory, string $baseDomain): void
    {
        foreach ($expectedAssetPaths as $expectedAssetPath) {
            $this->uriConverter->joinPaths($targetDirectory, $expectedAssetPath)->willReturn($targetDirectory . $expectedAssetPath);
            $this->uriConverter->joinUrlWithPath($baseDomain, $expectedAssetPath)->willReturn($baseDomain . $expectedAssetPath);
            $this->uriConverter->convertUriToOfflinePath($expectedAssetPath, $baseDomain)->willReturn($expectedAssetPath);
            $this->uriConverter->convertUriToOfflinePath($baseDomain . $expectedAssetPath, $baseDomain)->willReturn($expectedAssetPath);
            $this->uriConverter->convertUriToUrl($expectedAssetPath, $baseDomain)->willReturn($baseDomain . $expectedAssetPath);
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

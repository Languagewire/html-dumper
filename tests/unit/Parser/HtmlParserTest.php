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

namespace unit\LanguageWire\Parser;

use data\LanguageWire\HtmlFileProvider;
use LanguageWire\HtmlDumper\Parser\HtmlParser;
use LanguageWire\HtmlDumper\Parser\HtmlParsingException;
use LanguageWire\HtmlDumper\Uri\UriConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class HtmlParserTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    private const BASE_DOMAIN = 'http://example.com';

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $uriConverter;

    protected function setUp(): void
    {
        $this->uriConverter = $this->prophesize(UriConverter::class);
    }

    /**
     * @test
     * @dataProvider provideHtmlFiles
     */
    public function parseHtmlContent__WHEN_valid_html_is_provided_THEN_expected_results_are_returned(
        string $htmlContent,
        array $expectedAssetUrls
    ): void {
        $this->uriConverter->convertUriToOfflinePath(Argument::type('string'), self::BASE_DOMAIN)->willReturnArgument(0);
        $this->uriConverter->removeQueryParams(Argument::type('string'))->willReturnArgument(0);

        $parser = $this->parser();
        $assetUrls = $parser->parseHtmlContent($htmlContent, self::BASE_DOMAIN)->getAssetUris();
        $this->assertEqualsCanonicalizing($expectedAssetUrls, $assetUrls);
    }

    /**
     * @test
     */
    public function parseHtmlContent__WHEN_valid_html_is_provided_with_different_uri_schemes_THEN_output_html_is_converted(): void
    {
        $this->uriConverter->convertUriToOfflinePath('data/img/relative.png', self::BASE_DOMAIN)->willReturn('data/img/relative.png');
        $this->uriConverter->convertUriToOfflinePath('data/img/relative.png?query=param', self::BASE_DOMAIN)->willReturn('data/img/relative.png?query=param');
        $this->uriConverter->removeQueryParams('data/img/relative.png')->willReturn('data/img/relative.png');
        $this->uriConverter->removeQueryParams('data/img/relative.png?query=param')->willReturn('data/img/relative.png');

        $this->uriConverter->convertUriToOfflinePath('/data/img/absolute.png', self::BASE_DOMAIN)->willReturn('data/img/absolute.png');
        $this->uriConverter->convertUriToOfflinePath('/data/img/absolute.png?query=param', self::BASE_DOMAIN)->willReturn('data/img/absolute.png?query=param');
        $this->uriConverter->removeQueryParams('data/img/absolute.png')->willReturn('data/img/absolute.png');
        $this->uriConverter->removeQueryParams('data/img/absolute.png?query=param')->willReturn('data/img/absolute.png');

        $this->uriConverter->convertUriToOfflinePath('http://example.com/data/img/baseDomain.png', self::BASE_DOMAIN)->willReturn('data/img/baseDomain.png');
        $this->uriConverter->convertUriToOfflinePath('http://example.com/data/img/baseDomain.png?query=param', self::BASE_DOMAIN)->willReturn('data/img/baseDomain.png?query=param');
        $this->uriConverter->removeQueryParams('data/img/baseDomain.png')->willReturn('data/img/baseDomain.png');
        $this->uriConverter->removeQueryParams('data/img/baseDomain.png?query=param')->willReturn('data/img/baseDomain.png');

        $this->uriConverter->convertUriToOfflinePath('http://externalcontent.com/data/img/externalDomain.png', self::BASE_DOMAIN)->willReturn('externalcontent.com/data/img/externalDomain.png');
        $this->uriConverter->convertUriToOfflinePath('http://externalcontent.com/data/img/externalDomain.png?query=param', self::BASE_DOMAIN)->willReturn('externalcontent.com/data/img/externalDomain.png?query=param');
        $this->uriConverter->removeQueryParams('externalcontent.com/data/img/externalDomain.png')->willReturn('externalcontent.com/data/img/externalDomain.png');
        $this->uriConverter->removeQueryParams('externalcontent.com/data/img/externalDomain.png?query=param')->willReturn('externalcontent.com/data/img/externalDomain.png');

        $parser = $this->parser();

        $before = (new HtmlFileProvider())->getTestHtmlFilesUriTransformBefore();
        $expectedOutputHtml = (new HtmlFileProvider())->getTestHtmlFilesUriTransformAfter();

        $outputHtml = $parser->parseHtmlContent($before, self::BASE_DOMAIN)->getOutputCode();

        $this->assertEquals(
            $this->removeLineBreaks($expectedOutputHtml),
            $this->removeLineBreaks((string) $outputHtml)
        );
    }

    /**
     * @test
     */
    public function parseHtmlContent__WHEN_html_has_anchors_THEN_their_attributes_are_removed(): void
    {
        $parser = $this->parser();

        $before = (new HtmlFileProvider())->getTestHtmlFilesAnchorBefore();
        $expectedOutputHtml = (new HtmlFileProvider())->getTestHtmlFilesAnchorAfter();

        $outputHtml = $parser->parseHtmlContent($before, self::BASE_DOMAIN)->getOutputCode();

        $this->assertEquals(
            $this->removeLineBreaks($expectedOutputHtml),
            $this->removeLineBreaks((string) $outputHtml)
        );
    }

    /**
     * @test
     */
    public function parseHtmlContent__WHEN_html_has_srcsets_THEN_their_attributes_are_removed(): void
    {
        $this->uriConverter->convertUriToOfflinePath('data/img/screenshot.png', self::BASE_DOMAIN)->willReturn('data/img/screenshot.png');
        $this->uriConverter->removeQueryParams('data/img/screenshot.png')->willReturn('data/img/screenshot.png');

        $parser = $this->parser();

        $before = (new HtmlFileProvider())->getTestHtmlFilesSrcSetsBefore();
        $expectedOutputHtml = (new HtmlFileProvider())->getTestHtmlFilesSrcSetsAfter();

        $outputHtml = $parser->parseHtmlContent($before, self::BASE_DOMAIN)->getOutputCode();

        $this->assertEquals(
            $this->removeLineBreaks($expectedOutputHtml),
            $this->removeLineBreaks((string) $outputHtml)
        );
    }

    /**
     * @todo create an input that throws this exception. does it exist?

     public function parseHtmlContent__WHEN_invalid_html_is_provided_THEN_HtmlParsingException_is_thrown(): void {

        $this->expectException(HtmlParsingException::class);

        $htmlContent = "<phtnl><p><htpml></htpml></p></p><html><body><head><title>asd</title></head></body></html>";
        $parser = $this->parser();
        $parser->parseHtmlContent($htmlContent, 'http://example.com');
    }*/

    public function provideHtmlFiles(): array
    {
        return (new HtmlFileProvider())->getAllTestHtmlFiles();
    }

    private function parser(): HtmlParser
    {
        return new HtmlParser($this->uriConverter->reveal());
    }

    private function removeLineBreaks(string $text): string
    {
        return str_replace(array("\r", "\n"), '', $text);
    }
}

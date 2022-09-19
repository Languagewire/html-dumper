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
use LanguageWire\HtmlDumper\Uri\UriConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class HtmlParserTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

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
        $this->uriConverter->convertUriToOfflinePath(Argument::type('string'), 'http://example.com')->willReturnArgument(0);
        $this->uriConverter->cleanRelativePath(Argument::type('string'))->willReturnArgument(0);

        $parser = $this->parser();
        $assetUrls = $parser->parseHtmlContent($htmlContent, 'http://example.com')->getAssetUris();
        $this->assertEqualsCanonicalizing($expectedAssetUrls, $assetUrls);
    }

    /**
     * @test
     */
    public function parseHtmlContent__WHEN_valid_html_is_provided_with_different_uri_schemes_THEN_output_html_is_converted(): void
    {
        $this->uriConverter->convertUriToOfflinePath('data/img/relative.png', 'http://example.com')->willReturn('data/img/relative.png');
        $this->uriConverter->convertUriToOfflinePath('data/img/relative.png?query=param', 'http://example.com')->willReturn('data/img/relative.png?query=param');
        $this->uriConverter->cleanRelativePath('data/img/relative.png')->willReturn('data/img/relative.png');
        $this->uriConverter->cleanRelativePath('data/img/relative.png?query=param')->willReturn('data/img/relative.png');

        $this->uriConverter->convertUriToOfflinePath('/data/img/absolute.png', 'http://example.com')->willReturn('data/img/absolute.png');
        $this->uriConverter->convertUriToOfflinePath('/data/img/absolute.png?query=param', 'http://example.com')->willReturn('data/img/absolute.png?query=param');
        $this->uriConverter->cleanRelativePath('data/img/absolute.png')->willReturn('data/img/absolute.png');
        $this->uriConverter->cleanRelativePath('data/img/absolute.png?query=param')->willReturn('data/img/absolute.png');

        $this->uriConverter->convertUriToOfflinePath('http://example.com/data/img/baseDomain.png', 'http://example.com')->willReturn('data/img/baseDomain.png');
        $this->uriConverter->convertUriToOfflinePath('http://example.com/data/img/baseDomain.png?query=param', 'http://example.com')->willReturn('data/img/baseDomain.png?query=param');
        $this->uriConverter->cleanRelativePath('data/img/baseDomain.png')->willReturn('data/img/baseDomain.png');
        $this->uriConverter->cleanRelativePath('data/img/baseDomain.png?query=param')->willReturn('data/img/baseDomain.png');

        $this->uriConverter->convertUriToOfflinePath('http://externalcontent.com/data/img/externalDomain.png', 'http://example.com')->willReturn('externalcontent.com/data/img/externalDomain.png');
        $this->uriConverter->convertUriToOfflinePath('http://externalcontent.com/data/img/externalDomain.png?query=param', 'http://example.com')->willReturn('externalcontent.com/data/img/externalDomain.png?query=param');
        $this->uriConverter->cleanRelativePath('externalcontent.com/data/img/externalDomain.png')->willReturn('externalcontent.com/data/img/externalDomain.png');
        $this->uriConverter->cleanRelativePath('externalcontent.com/data/img/externalDomain.png?query=param')->willReturn('externalcontent.com/data/img/externalDomain.png');

        $parser = $this->parser();

        $before = (new HtmlFileProvider())->getTestHtmlFilesUriTransformBefore();
        $expectedOutputHtml = (new HtmlFileProvider())->getTestHtmlFilesUriTransformAfter();

        $outputHtml = $parser->parseHtmlContent($before, 'http://example.com')->getOutputHtml();

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

        $outputHtml = $parser->parseHtmlContent($before, 'http://example.com')->getOutputHtml();

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
        $this->uriConverter->convertUriToOfflinePath('data/img/screenshot.png', 'http://example.com')->willReturn('data/img/screenshot.png');
        $this->uriConverter->cleanRelativePath('data/img/screenshot.png')->willReturn('data/img/screenshot.png');

        $parser = $this->parser();

        $before = (new HtmlFileProvider())->getTestHtmlFilesSrcSetsBefore();
        $expectedOutputHtml = (new HtmlFileProvider())->getTestHtmlFilesSrcSetsAfter();

        $outputHtml = $parser->parseHtmlContent($before, 'http://example.com')->getOutputHtml();

        $this->assertEquals(
            $this->removeLineBreaks($expectedOutputHtml),
            $this->removeLineBreaks((string) $outputHtml)
        );
    }

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

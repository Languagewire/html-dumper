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

use data\LanguageWire\CssFileProvider;
use LanguageWire\HtmlDumper\Parser\CssParser;
use LanguageWire\HtmlDumper\Uri\UriConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class CssParserTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $uriConverter;

    protected function setUp(): void
    {
        $this->uriConverter = $this->prophesize(UriConverter::class);
        $this->uriConverter->prependParentDirectoryDoubleDots(Argument::type('string'), Argument::type('int'))->willReturnArgument(0);
    }

    /**
     * @test
     * @dataProvider provideCssFiles
     */
    public function parseCssContent__WHEN_valid_css_is_provided_THEN_expected_results_are_returned(string $cssContent, array $expectedAssetUrls): void
    {
        $this->uriConverter->convertUriToOfflinePath(Argument::type('string'), 'http://example.com')->willReturnArgument(0);
        $this->uriConverter->cleanRelativePath(Argument::type('string'))->willReturnArgument(0);

        $parser = $this->parser();
        $assetUrls = $parser->parseCssContent($cssContent, 'http://example.com')->getAssetUris();
        $this->assertEqualsCanonicalizing($expectedAssetUrls, $assetUrls);
    }

    /**
     * @test
     */
    public function parseCssContent__WHEN_valid_css_is_provided_with_different_uri_schemes_THEN_output_css_is_converted(): void
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

        $beforeCssContent = (new CssFileProvider())->getTestCssFilesUriTransformBefore();
        $expectedOutputCss = (new CssFileProvider())->getTestCssFilesUriTransformAfter();

        $outputCss = $parser->parseCssContent($beforeCssContent, 'http://example.com')->getOutputCode();

        $this->assertEquals(
            $expectedOutputCss,
            (string)$outputCss
        );
    }

    public function provideCssFiles(): array
    {
        return (new CssFileProvider())->getTestCssFiles();
    }

    private function parser(): CssParser
    {
        return new CssParser($this->uriConverter->reveal());
    }
}

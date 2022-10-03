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

use LanguageWire\HtmlDumper\Parser\CssParser;
use LanguageWire\HtmlDumper\Uri\UriConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class CssParserTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    private const BASE_DOMAIN = 'http://example.com';
    public const CSS_DIRECTORY_PATH = '/tmp/target/css/';

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
     */
    public function parseCssContent__WHEN_valid_css_is_provided_with_relative_paths_THEN_output_css_is_converted(): void
    {
        $cssContent = <<<EOF
.class {
    background: url('img/background.png');
}
EOF;
        $expectedCssContent = <<<EOF
.class {
    background: url('/tmp/target/css/img/background.png');
}
EOF;
        $baseDomain = self::BASE_DOMAIN;
        $cssDirectoryPath = self::CSS_DIRECTORY_PATH;

        $this->uriConverter->convertUriToOfflinePath('img/background.png', $baseDomain)->willReturn('img/background.png');
        $this->uriConverter->removeQueryParams('img/background.png')->willReturn('img/background.png');
        $this->uriConverter->joinPaths($cssDirectoryPath, 'img/background.png')->willReturn($cssDirectoryPath . 'img/background.png');

        $parser = $this->parser();

        $result = $parser->parseCssContent($cssContent, $cssDirectoryPath, $baseDomain);

        $this->assertEquals($expectedCssContent, (string) $result->getParsedCode());
    }

    /**
     * @test
     */
    public function parseCssContent__WHEN_valid_css_is_provided_with_absolute_base_urls_THEN_output_css_is_converted(): void
    {
        $cssContent = <<<EOF
.class {
    background: url('http://example.com/img/background.png');
}
EOF;
        $expectedCssContent = <<<EOF
.class {
    background: url('/tmp/target/css/img/background.png');
}
EOF;
        $baseDomain = self::BASE_DOMAIN;
        $cssDirectoryPath = self::CSS_DIRECTORY_PATH;

        $this->uriConverter->convertUriToOfflinePath('http://example.com/img/background.png', $baseDomain)->willReturn('img/background.png');
        $this->uriConverter->removeQueryParams('img/background.png')->willReturn('img/background.png');
        $this->uriConverter->joinPaths($cssDirectoryPath, 'img/background.png')->willReturn($cssDirectoryPath . 'img/background.png');

        $parser = $this->parser();

        $result = $parser->parseCssContent($cssContent, $cssDirectoryPath, $baseDomain);

        $this->assertEquals($expectedCssContent, (string) $result->getParsedCode());
    }

    /**
     * @test
     */
    public function parseCssContent__WHEN_valid_css_is_provided_with_absolute_external_urls_THEN_output_css_is_converted(): void
    {
        $cssContent = <<<EOF
.class {
    background: url('http://externaldomain.com/img/background.png');
}
EOF;
        $expectedCssContent = <<<EOF
.class {
    background: url('/tmp/target/css/externaldomain.com/img/background.png');
}
EOF;
        $baseDomain = self::BASE_DOMAIN;
        $cssDirectoryPath = self::CSS_DIRECTORY_PATH;

        $this->uriConverter->convertUriToOfflinePath('http://externaldomain.com/img/background.png', $baseDomain)->willReturn('externaldomain.com/img/background.png');
        $this->uriConverter->removeQueryParams('externaldomain.com/img/background.png')->willReturn('externaldomain.com/img/background.png');
        $this->uriConverter->joinPaths($cssDirectoryPath, 'externaldomain.com/img/background.png')->willReturn($cssDirectoryPath . 'externaldomain.com/img/background.png');

        $parser = $this->parser();

        $result = $parser->parseCssContent($cssContent, $cssDirectoryPath, $baseDomain);

        $this->assertEquals($expectedCssContent, (string) $result->getParsedCode());
    }

    /**
     * @test
     */
    public function parseCssContent__WHEN_valid_css_is_provided_with_directory_transversal_THEN_output_css_is_converted(): void
    {
        $cssContent = <<<EOF
.class {
    background: url('../img/background.png');
}
EOF;
        $expectedCssContent = <<<EOF
.class {
    background: url('/tmp/target/css/../img/background.png');
}
EOF;
        $baseDomain = self::BASE_DOMAIN;
        $cssDirectoryPath = self::CSS_DIRECTORY_PATH;

        $this->uriConverter->convertUriToOfflinePath('../img/background.png', $baseDomain)->willReturn('../img/background.png');
        $this->uriConverter->removeQueryParams('../img/background.png')->willReturn('../img/background.png');
        $this->uriConverter->joinPaths($cssDirectoryPath, '../img/background.png')->willReturn($cssDirectoryPath . '../img/background.png');

        $parser = $this->parser();

        $result = $parser->parseCssContent($cssContent, $cssDirectoryPath, $baseDomain);

        $this->assertEquals($expectedCssContent, (string) $result->getParsedCode());
    }

    private function parser(): CssParser
    {
        return new CssParser($this->uriConverter->reveal());
    }
}

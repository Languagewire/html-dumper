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

namespace LanguageWire\HtmlDumper\Parser;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

class CssParseResult
{
    /**
     * @var string
     */
    private $outputCss;
    /**
     * @var string[]
     */
    private $assetUris;

    /**
     * @param string $outputCss
     * @param string[] $assetUris
     */
    public function __construct(string $outputCss, array $assetUris)
    {
        $this->outputCss = $outputCss;
        $this->assetUris = $assetUris;
    }

    public function getOutputCss(): StreamInterface
    {
        $handle = fopen('php://memory', "rw+");
        fwrite($handle, $this->outputCss);
        fseek($handle, 0);
        return new Stream($handle);
    }

    /**
     * @return string[]
     */
    public function getAssetUris(): array
    {
        return $this->assetUris;
    }
}
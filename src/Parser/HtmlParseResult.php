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

class HtmlParseResult
{
    /**
     * @var string
     */
    private $outputHtml;
    /**
     * @var string[]
     */
    private $assetUris;

    /**
     * @param string $outputHtml
     * @param string[] $assetUris
     */
    public function __construct(string $outputHtml, array $assetUris)
    {
        $this->outputHtml = $outputHtml;
        $this->assetUris = $assetUris;
    }

    public function getOutputHtml(): StreamInterface
    {
        $handle = fopen('php://memory', "rw+");
        fwrite($handle, $this->outputHtml);
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

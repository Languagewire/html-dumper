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

class ParseResult
{
    /**
     * @var string
     */
    private $outputCode;
    /**
     * @var string[]
     */
    private $assetUris;

    /**
     * @param string $outputCode
     * @param string[] $assetUris
     */
    public function __construct(string $outputCode, array $assetUris)
    {
        $this->outputCode = $outputCode;
        $this->assetUris = $assetUris;
    }

    public function getParsedCode(): string
    {
        return $this->outputCode;
    }

    /**
     * @return string[]
     */
    public function getAssetUris(): array
    {
        return $this->assetUris;
    }
}

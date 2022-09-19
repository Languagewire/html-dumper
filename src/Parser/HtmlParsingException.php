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

class HtmlParsingException extends \Exception
{
    /**
     * @var string
     */
    private $htmlContent;

    public function __construct(string $htmlContent)
    {
        parent::__construct("Could not load HTML content");
        $this->htmlContent = $htmlContent;
    }

    /**
     * @return string
     */
    public function getHtmlContent(): string
    {
        return $this->htmlContent;
    }
}

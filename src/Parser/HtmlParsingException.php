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
    /**
     * @var string
     */
    private $url;

    public function __construct(string $htmlContent, string $url)
    {
        parent::__construct("Could not load HTML content from URL $url");
        $this->htmlContent = $htmlContent;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getHtmlContent(): string
    {
        return $this->htmlContent;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}

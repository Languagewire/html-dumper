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

class HtmlGenerationException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Could not generate HTML content for the given DOMDocument");
    }
}

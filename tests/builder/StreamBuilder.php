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

namespace builder\LanguageWire;

use GuzzleHttp\Psr7\Stream;

final class StreamBuilder
{
    /**
     * @var string
     */
    private $body = "";

    public function withBodyString(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function build(): Stream
    {
        $handle = fopen('php://memory', "rw+");
        fwrite($handle, $this->body);
        fseek($handle, 0);
        return new Stream($handle);
    }
}

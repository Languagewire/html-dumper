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

use Drupal\languagewire_tmgmt_connector\Adapter\PreviewSite\PreviewSiteBuild;
use Prophecy\Prophet;
use Psr\Http\Message\StreamInterface;

final class StreamInterfaceBuilder
{
    /**
     * @var string
     */
    private $body = "";

    public function withBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function build(): StreamInterface
    {
        $prophet = new Prophet();
        $stream = $prophet->prophesize(StreamInterface::class);

        $stream->__toString()->willReturn($this->body);

        return $stream->reveal();
    }
}
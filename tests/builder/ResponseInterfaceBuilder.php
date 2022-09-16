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
use Psr\Http\Message\ResponseInterface;

final class ResponseInterfaceBuilder
{
    /**
     * @var string
     */
    private $body = "";

    /**
     * @var int
     */
    private $statusCode = 200;

    public function withBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function withStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function build(): ResponseInterface
    {
        $prophet = new Prophet();
        $response = $prophet->prophesize(ResponseInterface::class);
        $stream = (new StreamInterfaceBuilder())->withBody($this->body)->build();

        $response->getStatusCode()->willReturn($this->statusCode);
        $response->getBody()->willReturn($stream);

        return $response->reveal();
    }
}
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

use GuzzleHttp\Psr7\Response;
use Prophecy\Prophet;
use Psr\Http\Message\ResponseInterface;

final class ResponseBuilder
{
    /**
     * @var string
     */
    private $body = "";

    /**
     * @var int
     */
    private $statusCode = 200;

    public function withBodyString(string $body): self
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
        $response = new Response($this->statusCode, [], $this->body);

        return $response;
    }
}

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

namespace LanguageWire\HtmlDumper\Http;

use GuzzleHttp\ClientInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class NullableHttpClient
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $method
     * @param string $assetUrl
     * @return ResponseInterface|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $method, string $assetUrl): ?ResponseInterface
    {
        try {
            return $this->client->request($method, $assetUrl);
        } catch (RequestExceptionInterface $e) {
            return null;
        }
    }
}

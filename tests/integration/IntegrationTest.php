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

namespace integration\LanguageWire;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use LanguageWire\HtmlDumper\Service\PageDownloader;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use traits\LanguageWire\TemporaryFileTestsTrait;

class IntegrationTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    use TemporaryFileTestsTrait;

    private const TEMP_TARGET_DIRECTORY = "/app/tests/target";

    protected function setUp(): void
    {
        $baseTargetPath = dirname(self::TEMP_TARGET_DIRECTORY);

        if (!is_dir($baseTargetPath)) {
            mkdir($baseTargetPath);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir(self::TEMP_TARGET_DIRECTORY)) {
            // $this->recursivelyDeleteDirectory("/app/tests/target");
        }
    }

    /**
     * @test
     */
    public function PageDownloader__WHEN_downloading_external_url_THEN_files_are_created(): void
    {
        $baseDomain = "https://www.languagewire.com/en/about-us/locations";
        $targetDirectory = self::TEMP_TARGET_DIRECTORY;

        $pageDownloader = $this->pageDownloader();

        $pageDownloader->download($baseDomain, $targetDirectory);

        $this->assertDirectoryExists($targetDirectory);
        $this->assertFileExists("$targetDirectory/index.html");
    }

    private function pageDownloader(): PageDownloader
    {
        $logger = new Logger('Logger');
        $logger->pushHandler(new ErrorLogHandler());
        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                $logger,
                new MessageFormatter()
            )
        );
        $client = new \GuzzleHttp\Client(
            [
                'handler' => $stack,
            ]
        );
        return new PageDownloader($client);
    }
}

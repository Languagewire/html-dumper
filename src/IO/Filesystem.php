<?php

declare(strict_types=1);

/*
 * This file is part of the LanguageWire Connector package.
 *
 * (c) LanguageWire <contact@languagewire.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 */

namespace LanguageWire\HtmlDumper\IO;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

class Filesystem
{
    /**
     * @throws IOException
     */
    public function createDirectory(string $path, bool $recursive = false): void
    {
        $result = @mkdir($path, 0777, $recursive);

        if ($result === false) {
            throw IOException::createDirectory($path);
        }
    }

    /**
     * @throws IOException
     */
    public function createFile(string $path, StreamInterface $content, bool $createParentDirectory = false): void
    {
        if ($createParentDirectory) {
            $this->createParentDirectory($path);
        }

        $handle = @fopen($path, 'x');

        if ($handle === false) {
            throw IOException::createFile($path);
        }

        $writeResult = @fwrite($handle, $content->getContents());

        if ($writeResult === false) {
            throw IOException::createFile($path);
        }

        fclose($handle);
    }

    /**
     * @throws IOException
     */
    public function writeToFile(string $path, StreamInterface $content): void
    {
        if (file_exists($path) === false) {
            throw IOException::writeToFile($path);
        }

        $handle = @fopen($path, 'w');

        if ($handle === false) {
            throw IOException::writeToFile($path);
        }

        $writeResult = @fwrite($handle, (string) $content);

        if ($writeResult === false) {
            throw IOException::writeToFile($path);
        }

        fclose($handle);
    }

    /**
     * @throws IOException
     */
    public function readFile(string $path): StreamInterface
    {
        $handle = @fopen($path, 'r');

        if ($handle === false) {
            throw IOException::readFile($path);
        }

        return new Stream($handle);
    }

    /**
     * @throws IOException
     */
    public function createParentDirectory(string $path)
    {
        $parentDirectory = dirname($path);

        if (!\is_dir($parentDirectory)) {
            $this->createDirectory($parentDirectory, true);
        }
    }
}
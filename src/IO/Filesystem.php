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

namespace LanguageWire\HtmlDumper\IO;

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
    public function writeToFile(string $path, string $content, bool $createParentDirectory = false): void
    {
        if ($createParentDirectory) {
            $this->createParentDirectory($path);
        }

        if (file_exists($path) === false) {
            $touchResult = @touch($path);

            if ($touchResult === false) {
                throw IOException::createFile($path);
            }
        }

        if (!is_writable($path)) {
            throw IOException::writeToFile($path);
        }

        $handle = @fopen($path, 'w');

        if ($handle === false) {
            throw IOException::writeToFile($path);
        }

        $writeResult = @fwrite($handle, $content);

        if ($writeResult === false) {
            throw IOException::writeToFile($path);
        }

        fclose($handle);
    }

    /**
     * @throws IOException
     */
    public function readFile(string $path): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw IOException::readFile($path);
        }

        return $contents;
    }

    public function getParentDirectory(string $path): string
    {
        return dirname($path);
    }

    /**
     * @throws IOException
     */
    public function createParentDirectory(string $path)
    {
        $parentDirectory = $this->getParentDirectory($path);

        if (!\is_dir($parentDirectory)) {
            $this->createDirectory($parentDirectory, true);
        }
    }
}

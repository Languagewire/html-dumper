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

class IOException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function createDirectory(string $path): self
    {
        return new self("Could not create directory at $path");
    }

    public static function createFile(string $path): self
    {
        return new self("Could not create file at $path");
    }

    public static function writeToFile(string $path): self
    {
        return new self("Could not write to file at $path");
    }

    public static function readFile(string $path): self
    {
        return new self("Could not read file at $path");
    }
}

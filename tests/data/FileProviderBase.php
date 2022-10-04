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

namespace data\LanguageWire;

use PHPUnit\TextUI\TestFileNotFoundException;

abstract class FileProviderBase
{
    protected function readFile(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new TestFileNotFoundException($path);
        }

        return $contents;
    }

    protected function mapToPHPUnitProviderFormat(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = [
                $this->readFile($value[0]),
                $value[1]
            ];
        }

        return $result;
    }
}

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

namespace traits\LanguageWire;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait TemporaryFileTestsTrait
{
    protected function createTemporaryDirectory(string $directoryName): string
    {
        $temporaryDirectoryNamePath = sys_get_temp_dir() . '/' . $directoryName;

        if (!is_dir($temporaryDirectoryNamePath)) {
            mkdir($temporaryDirectoryNamePath, 0777, true);
        }

        return $temporaryDirectoryNamePath;
    }

    protected function recursivelyDeleteDirectory(string $directoryPath): void
    {
        // Iterate over all files first and then directories
        // with RecursiveIteratorIterator::CHILD_FIRST
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        $deletedAll = true;

        foreach ($files as $file) {
            if ($file->isDir()) {
                $deletedAll = $deletedAll && @rmdir($file->getRealPath());
            } else {
                $deletedAll = $deletedAll && @unlink($file->getRealPath());
            }
        }

        $deletedAll = $deletedAll && @rmdir($directoryPath);

        if (!$deletedAll) {
            throw new \Exception("Could not delete all files from temporary directory $directoryPath");
        }
    }
}

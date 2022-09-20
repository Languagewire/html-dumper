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

namespace unit\LanguageWire\IO;

use LanguageWire\HtmlDumper\IO\Filesystem;
use LanguageWire\HtmlDumper\IO\IOException;
use PHPUnit\Framework\TestCase;
use traits\LanguageWire\TemporaryFileTestsTrait;

class FilesystemTest extends TestCase
{
    private const TEMP_TARGET_DIRECTORY_NAME = "file-system-target";
    use TemporaryFileTestsTrait;

    private $tempTargetDirectory;

    protected function setUp(): void
    {
        $this->tempTargetDirectory = $this->createTemporaryDirectory(self::TEMP_TARGET_DIRECTORY_NAME);
        mkdir($this->tempTargetDirectory);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempTargetDirectory)) {
            $this->recursivelyDeleteDirectory($this->tempTargetDirectory);
        }
    }

    /**
     * @test
     */
    public function createFile__WHEN_file_can_be_created_THEN_it_is_created()
    {
        $targetPath = $this->tempTargetDirectory . '/index.html';

        $sut = $this->filesystem();

        $sut->createFile($targetPath, $this->createStreamFromString("content"));

        $this->assertEquals("content", file_get_contents($targetPath));
    }

    /**
     * @test
     */
    public function createFile__WHEN_file_cannot_be_created_THEN_IOException_is_thrown()
    {
        $targetPath = $this->tempTargetDirectory . '/sub/index.html';

        $this->expectException(IOException::class);

        $sut = $this->filesystem();

        $sut->createFile($targetPath, $this->createStreamFromString("content"));
    }

    /**
     * @test
     */
    public function createDirectory__WHEN_directory_can_be_created_THEN_it_is_created()
    {
        $targetPath = $this->tempTargetDirectory . '/sub';

        $sut = $this->filesystem();

        $sut->createDirectory($targetPath);

        $this->assertTrue(is_dir($targetPath));
    }

    /**
     * @test
     */
    public function createDirectory__WHEN_directory_already_exists_THEN_IOException_is_thrown()
    {
        $targetPath = $this->tempTargetDirectory . '/sub';


        $this->expectException(IOException::class);

        mkdir($targetPath);

        $sut = $this->filesystem();

        $sut->createDirectory($targetPath);
    }

    /**
     * @test
     */
    public function createDirectory__WHEN_directory_has_subfolders_and_isnt_recursive_THEN_IOException_is_thrown()
    {
        $targetPath = $this->tempTargetDirectory . '/sub/folders';

        $this->expectException(IOException::class);

        $sut = $this->filesystem();

        $sut->createDirectory($targetPath);
    }

    /**
     * @test
     */
    public function writeToFile__WHEN_file_can_be_written_to_THEN_contents_are_updated()
    {
        $targetPath = $this->tempTargetDirectory . '/index.html';

        file_put_contents($targetPath, "original content");

        $sut = $this->filesystem();

        $sut->writeToFile($targetPath, $this->createStreamFromString("updated content"));

        $this->assertEquals("updated content", file_get_contents($targetPath));
    }

    /**
     * @test
     */
    public function writeToFile__WHEN_file_does_not_exist_THEN_IOException_is_thrown()
    {
        $targetPath = $this->tempTargetDirectory . '/index.html';

        $this->expectException(IOException::class);

        $sut = $this->filesystem();

        $sut->writeToFile($targetPath, $this->createStreamFromString("content"));
    }

    /**
     * @test
     */
    public function readFile__WHEN_file_can_be_read_THEN_stream_is_returned()
    {
        $targetPath = $this->tempTargetDirectory . '/index.html';

        file_put_contents($targetPath, "original content");

        $sut = $this->filesystem();

        $result = $sut->readFile($targetPath);

        $this->assertEquals("original content", $result->getContents());
    }

    /**
     * @test
     */
    public function readFile__WHEN_file_cannot_be_read_THEN_stream_is_returned()
    {
        $targetPathNonExisting = $this->tempTargetDirectory . '/index.html';

        $this->expectException(IOException::class);

        $sut = $this->filesystem();

        $sut->readFile($targetPathNonExisting);
    }

    /**
     * @test
     */
    public function getParentDirectory__WHEN_a_file_is_provided_THEN_its_parent_is_returned()
    {
        $targetPath = $this->tempTargetDirectory . '/sub';

        $this->expectException(IOException::class);

        $sut = $this->filesystem();

        $this->assertEquals($this->tempTargetDirectory, $sut->getParentDirectory($targetPath));
    }

    /**
     * @test
     */
    public function createParentDirectory__WHEN_file_parent_directory_can_be_created_THEN_it_is_created()
    {
        $targetPathParentDirectory = $this->tempTargetDirectory . '/sub';
        $targetPath = $this->tempTargetDirectory . '/sub/index.html';

        $sut = $this->filesystem();

        $sut->createParentDirectory($targetPath);

        $this->assertDirectoryExists($targetPathParentDirectory);
    }

    private function filesystem(): Filesystem
    {
        return new Filesystem();
    }
}

<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use Koriym\FileUpload\Exception\FileNotFoundException;
use PHPUnit\Framework\TestCase;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class FileUploadFromFileTest extends TestCase
{
    private string $testImagePath;
    private string $testTextPath;
    private string $emptyFilePath;

    protected function setUp(): void
    {
        // テスト用のJPEG画像を作成
        $this->testImagePath = tempnam(sys_get_temp_dir(), 'test_image_') . '.jpg';
        file_put_contents($this->testImagePath, base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/4QBmRXhpZgAATU0AKgAAAAgABAEaAAUAAAABAAAAPgEbAAUAAAABAAAARgEoAAMAAAABAAIAAAExAAIAAAAQAAAATgAAAAAAAABgAAAAAQAAAGAAAAABcGFpbnQubmV0IDUuMC41AP/bAEMABQMEBAQDBQQEBAUFBQYHDAgHBwcHDwsLCQwRDxISEQ8RERMWHBcTFBoVEREYIRgaHR0fHx8TFyIkIh4kHB4fHv/bAEMBBQUFBwYHDggIDh4UERQeHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHv/AABEIAAEAAQMBIgACEQEDEQH/xAAVAAEBAAAAAAAAAAAAAAAAAAAABv/EAB0QAAICAgMBAAAAAAAAAAAAAAABAgMEBREhQRP/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AoNxbhMLl7knbXOUJvmDhLx/YAg//2Q=='));

        // テスト用のテキストファイルを作成
        $this->testTextPath = tempnam(sys_get_temp_dir(), 'test_text_') . '.txt';
        file_put_contents($this->testTextPath, 'Hello, World!');

        // 空のファイルを作成
        $this->emptyFilePath = tempnam(sys_get_temp_dir(), 'empty_') . '.txt';
        file_put_contents($this->emptyFilePath, '');
    }

    protected function tearDown(): void
    {
        @unlink($this->testImagePath);
        @unlink($this->testTextPath);
        @unlink($this->emptyFilePath);
    }

    public function testFromFileSuccess(): void
    {
        $upload = FileUpload::fromFile($this->testImagePath);
        $this->assertInstanceOf(FileUpload::class, $upload);
        @unlink($upload->tmpName);
    }

    public function testFromFileEmpty(): void
    {
        $upload = FileUpload::fromFile($this->emptyFilePath);

        $this->assertInstanceOf(FileUpload::class, $upload);
        $this->assertSame(0, $upload->size);
        $this->assertSame('application/x-empty', $upload->type);
        @unlink($upload->tmpName);
    }

    public function testFromFileWithValidation(): void
    {
        $validationOptions = [
            'maxSize' => 1024 * 1024,
            'allowedTypes' => ['image/jpeg'],
            'allowedExtensions' => ['jpg']
        ];

        $upload = FileUpload::fromFile($this->testImagePath, $validationOptions);
        $this->assertInstanceOf(FileUpload::class, $upload);
        @unlink($upload->tmpName);
    }

    public function testFromFileWithValidationFailure(): void
    {
        $validationOptions = [
            'allowedTypes' => ['application/pdf']
        ];

        $upload = FileUpload::fromFile($this->testTextPath, $validationOptions);
        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
    }

    public function testFileNotFound(): void
    {
        $nonExistentPath = '/path/to/nonexistent/file.jpg';
        try {
            FileUpload::fromFile($nonExistentPath);
            $this->fail('Exception was not thrown');
        } catch (FileNotFoundException $e) {
            $this->assertSame($nonExistentPath, $e->getMessage());
        }
    }
}

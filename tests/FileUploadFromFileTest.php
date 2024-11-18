<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use Koriym\FileUpload\Exception\FileUploadException;
use PHPUnit\Framework\TestCase;

use function base64_decode;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class FileUploadFromFileTest extends TestCase
{
    private string $testImagePath;
    private string $testTextPath;

    protected function setUp(): void
    {
        // Create JPEG images for testing
        $this->testImagePath = tempnam(sys_get_temp_dir(), 'test_image_') . '.jpg';
        file_put_contents($this->testImagePath, base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/4QBmRXhpZgAATU0AKgAAAAgABAEaAAUAAAABAAAAPgEbAAUAAAABAAAARgEoAAMAAAABAAIAAAExAAIAAAAQAAAATgAAAAAAAABgAAAAAQAAAGAAAAABcGFpbnQubmV0IDUuMC41AP/bAEMABQMEBAQDBQQEBAUFBQYHDAgHBwcHDwsLCQwRDxISEQ8RERMWHBcTFBoVEREYIRgaHR0fHx8TFyIkIh4kHB4fHv/bAEMBBQUFBwYHDggIDh4UERQeHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHv/AABEIAAEAAQMBIgACEQEDEQH/xAAVAAEBAAAAAAAAAAAAAAAAAAAABv/EAB0QAAICAgMBAAAAAAAAAAAAAAABAgMEBREhQRP/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AoNxbhMLl7knbXOUJvmDhLx/YAg//2Q=='));

        // Create text for testing
        $this->testTextPath = tempnam(sys_get_temp_dir(), 'test_text_') . '.txt';
        file_put_contents($this->testTextPath, 'Hello, World!');
    }

    protected function tearDown(): void
    {
        @unlink($this->testImagePath);
        @unlink($this->testTextPath);
    }

    public function testFromFileSuccess(): void
    {
        $upload = FileUpload::fromFile($this->testImagePath);

        $this->assertInstanceOf(FileUpload::class, $upload);
        $this->assertStringEndsWith('.jpg', $upload->name);
        $this->assertGreaterThan(0, $upload->size);
        $this->assertFileExists($upload->tmpName);
        $this->assertEquals('image/jpeg', $upload->type);

        // Clean up temporary file
        @unlink($upload->tmpName);
    }

    public function testFromFileWithValidation(): void
    {
        $validationOptions = [
            'maxSize' => 1024 * 1024,  // 1MB
            'allowedTypes' => ['image/jpeg'],
            'allowedExtensions' => ['jpg'],
        ];

        $upload = FileUpload::fromFile($this->testImagePath, $validationOptions);
        $this->assertInstanceOf(FileUpload::class, $upload);

        // Clean up temporary file
        @unlink($upload->tmpName);
    }

    public function testFromFileWithValidationFailure(): void
    {
        $validationOptions = [
            'allowedTypes' => ['application/pdf'],
        ];

        $upload = FileUpload::fromFile($this->testTextPath, $validationOptions);
        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertIsString($upload->message);
        $this->assertStringContainsString('type text/plain is not allowed', $upload->message);
    }

    public function testFromFileNonExistentFile(): void
    {
        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('File not found');

        FileUpload::fromFile('/path/to/nonexistent/file.jpg');
    }
}

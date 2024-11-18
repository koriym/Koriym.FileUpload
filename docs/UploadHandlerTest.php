<?php

use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;

class UploadHandlerTest extends TestCase
{
    private array $originalFiles;

    protected function setUp(): void
    {
        $this->originalFiles = $_FILES;
    }

    protected function tearDown(): void
    {
        $_FILES = $this->originalFiles;
    }

    public function testSuccessfulUpload(): void
    {
        // Setup test file upload
        $upload = FileUpload::fromFile(__DIR__ . '/fixtures/valid-image.jpg');
        $_FILES['upload'] = $upload->toArray();

        $handler = new YourUploadHandler();

        // Test your code that depends on $_FILES
        $result = $handler->handleUpload();
        $this->assertFileExists('/path/to/uploads/valid-image.jpg');
        $this->assertEquals('image/jpeg', $result->getContentType());
        $this->assertGreaterThan(0, $result->getSize());
    }

    public function testInvalidFileType(): void
    {
        $upload = FileUpload::fromFile(__DIR__ . '/fixtures/invalid.exe');
        $_FILES['upload'] = $upload->toArray();

        $handler = new YourUploadHandler();

        $this->expectException(InvalidFileTypeException::class);
        $handler->handleUpload();
    }
}

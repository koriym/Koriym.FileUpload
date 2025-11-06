<?php

declare(strict_types=1);

namespace Koriym\FileUpload\Example;

require_once __DIR__ . '/UploadHandler.php';

use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function sys_get_temp_dir;
use function unlink;

/**
 * Example test showing how to test file upload handling
 *
 * This demonstrates using fromFile() and toArray() to test code that depends on $_FILES
 */
final class UploadHandlerTest extends TestCase
{
    private string $uploadDir;
    private string $fixturesDir;
    private UploadHandler $handler;

    protected function setUp(): void
    {
        // Create temporary directories for testing
        $this->uploadDir = sys_get_temp_dir() . '/test_uploads_' . uniqid();
        $this->fixturesDir = __DIR__ . '/fixtures';

        if (! is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0755, true);
        }

        // Create test image if it doesn't exist
        $testImage = $this->fixturesDir . '/test-image.jpg';
        if (! file_exists($testImage)) {
            // Minimal valid JPEG
            $jpegData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCgAH//2Q==');
            file_put_contents($testImage, $jpegData);
        }

        // Create test text file if it doesn't exist
        $testText = $this->fixturesDir . '/test.txt';
        if (! file_exists($testText)) {
            file_put_contents($testText, 'This is a test file');
        }

        $this->handler = new UploadHandler($this->uploadDir);
    }

    protected function tearDown(): void
    {
        // Clean up uploaded files
        if (is_dir($this->uploadDir)) {
            $files = glob($this->uploadDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }

            rmdir($this->uploadDir);
        }
    }

    public function testSuccessfulUpload(): void
    {
        // Create FileUpload from actual file
        $upload = FileUpload::fromFile($this->fixturesDir . '/test-image.jpg');

        // Convert to $_FILES format for testing
        $filesData = $upload->toArray();

        // Test the handler
        $result = $this->handler->handleUpload($filesData);

        // Verify the result
        $this->assertFileExists($result->path);
        $this->assertSame('test-image.jpg', $result->originalName);
        $this->assertSame('image/jpeg', $result->mimeType);
        $this->assertGreaterThan(0, $result->size);
    }

    public function testInvalidFileType(): void
    {
        // Test with text file (not in allowed types)
        $upload = FileUpload::fromFile($this->fixturesDir . '/test.txt');
        $filesData = $upload->toArray();

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('not allowed');

        $this->handler->handleUpload($filesData);
    }

    public function testFileTooLarge(): void
    {
        // Create handler with very small size limit
        $handler = new UploadHandler($this->uploadDir, maxSize: 10);

        $upload = FileUpload::fromFile($this->fixturesDir . '/test-image.jpg');
        $filesData = $upload->toArray();

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('exceeds maximum allowed size');

        $handler->handleUpload($filesData);
    }
}

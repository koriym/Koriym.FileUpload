<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use PHPUnit\Framework\TestCase;

use const UPLOAD_ERR_INI_SIZE;

/** @psalm-import-type UploadedFile from AbstractFileUpload */
class ErrorFileUploadTest extends TestCase
{
    /** @var UploadedFile */
    private array $fileData;

    protected function setUp(): void
    {
        $this->fileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/phpXXXXXX',
            'error' => UPLOAD_ERR_INI_SIZE,
        ];
    }

    public function testErrorMessage(): void
    {
        $upload = new ErrorFileUpload($this->fileData);

        $this->assertStringContainsString('upload_max_filesize', $upload->message);
    }

    public function testCustomErrorMessage(): void
    {
        $customMessage = 'Custom error message';
        $upload = new ErrorFileUpload($this->fileData, $customMessage);

        $this->assertEquals($customMessage, $upload->message);
    }

    public function testNormalErrorCode(): void
    {
        $message = 'Something went wrong';
        /** @var UploadedFile */
        $data = [
            ...$this->fileData,
            'error' => 999, // Unknown error code
        ];

        $upload = new ErrorFileUpload($data, $message);

        $this->assertEquals($message, $upload->message);
    }
}

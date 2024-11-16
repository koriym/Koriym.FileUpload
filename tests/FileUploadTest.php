<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use PHPUnit\Framework\TestCase;

use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_OK;

/**
 * @psalm-import-type UploadedFile from AbstractFileUpload
 * @psalm-import-type ValidationOptions from AbstractFileUpload
 */
class FileUploadTest extends TestCase
{
    /** @var UploadedFile */
    private array $validFileData;

    protected function setUp(): void
    {
        $this->validFileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/phpXXXXXX',
            'error' => UPLOAD_ERR_OK,
        ];
    }

    public function testCreateSuccessfulUpload(): void
    {
        $upload = FileUpload::create($this->validFileData);

        $this->assertInstanceOf(FileUpload::class, $upload);
        $this->assertEquals('test.jpg', $upload->name);
        $this->assertEquals('image/jpeg', $upload->type);
        $this->assertEquals(1024, $upload->size);
        $this->assertEquals('/tmp/phpXXXXXX', $upload->tmpName);
        $this->assertEquals(UPLOAD_ERR_OK, $upload->error);
        $this->assertEquals('jpg', $upload->extension);
    }

    public function testCreateWithInvalidFileData(): void
    {
        /** @var array{name: string} */
        $invalidFileData = [
            'name' => 'test.jpg',
            // completely invalid data
        ];

        $upload = FileUpload::create($invalidFileData);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertEquals('Invalid file data structure', $upload->message);
    }

    public function testCreateWithUploadError(): void
    {
        /** @var UploadedFile */
        $fileDataWithError = [
            ...$this->validFileData,
            'error' => UPLOAD_ERR_INI_SIZE,
        ];

        $upload = FileUpload::create($fileDataWithError);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertStringContainsString('upload_max_filesize', $upload->message);
    }

    /** @return array<string, array{options: array}> */
    public function validationOptionsProvider(): array
    {
        return [
            'max size' => ['options' => ['maxSize' => 500]],
            'allowed types' => ['options' => ['allowedTypes' => ['image/png']]],
            'allowed extensions' => ['options' => ['allowedExtensions' => ['png']]],
        ];
    }

    /** @dataProvider validationOptionsProvider */
    public function testValidationFails(array $options): void
    {
        $upload = FileUpload::create($this->validFileData, $options);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertNotNull($upload->message);
    }

    public function testValidationMaxSize(): void
    {
        /** @var ValidationOptions */
        $options = ['maxSize' => 500];

        $upload = FileUpload::create($this->validFileData, $options);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertStringContainsString('exceeds maximum allowed size', $upload->message);
    }

    public function testValidationAllowedTypes(): void
    {
        /** @var ValidationOptions */
        $options = ['allowedTypes' => ['image/png']];

        $upload = FileUpload::create($this->validFileData, $options);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertStringContainsString('type image/jpeg is not allowed', $upload->message);
    }

    public function testValidationAllowedExtensions(): void
    {
        /** @var ValidationOptions */
        $options = ['allowedExtensions' => ['png']];

        $upload = FileUpload::create($this->validFileData, $options);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertStringContainsString('extension jpg is not allowed', $upload->message);
    }

    public function testIsImage(): void
    {
        $imageUpload = FileUpload::create($this->validFileData);
        $this->assertInstanceOf(FileUpload::class, $imageUpload);
        $this->assertTrue($imageUpload->isImage());

        /** @var UploadedFile */
        $nonImageData = [
            ...$this->validFileData,
            'type' => 'application/pdf',
        ];

        $nonImageUpload = FileUpload::create($nonImageData);
        $this->assertInstanceOf(FileUpload::class, $nonImageUpload);
        $this->assertFalse($nonImageUpload->isImage());
    }
}

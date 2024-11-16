<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

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
            // missing required fields
        ];

        $upload = FileUpload::create($invalidFileData);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertEquals('Invalid file data structure', (string) $upload->message);
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
        $this->assertStringContainsString('upload_max_filesize', (string) $upload->message);
    }

    /** @return array<ValidationOptions> */
    public function validationOptionsProvider(): array
    {
        return [
            'max size' => ['options' => ['maxSize' => 500]],
            'allowed types' => ['options' => ['allowedTypes' => ['image/png']]],
            'allowed extensions' => ['options' => ['allowedExtensions' => ['png']]],
        ];
    }

    /**
     * @param ValidationOptions $options
     *
     * @dataProvider validationOptionsProvider
     */
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
        $this->assertStringContainsString('exceeds maximum allowed size', (string) $upload->message);
    }

    public function testValidationAllowedTypes(): void
    {
        /** @var ValidationOptions */
        $options = ['allowedTypes' => ['image/png']];

        $upload = FileUpload::create($this->validFileData, $options);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertStringContainsString('type image/jpeg is not allowed', (string) $upload->message);
    }

    public function testValidationAllowedExtensions(): void
    {
        /** @var ValidationOptions */
        $options = ['allowedExtensions' => ['png']];

        $upload = FileUpload::create($this->validFileData, $options);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertStringContainsString('extension jpg is not allowed', (string) $upload->message);
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

    /**
     * Note: move_uploaded_file() only works with actual uploaded files.
     */
    public function testMove(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tmpFile, 'test data');

        /** @var UploadedFile */
        $fileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
        ];

        $upload = FileUpload::create($fileData);
        $this->assertInstanceOf(FileUpload::class, $upload);

        $destination = sys_get_temp_dir() . '/moved_test.jpg';
        $result = $upload->move($destination);

        $this->assertTrue($result);
        $this->assertFileExists($destination);
        $this->assertStringEqualsFile($destination, 'test data');
        @unlink($destination);
    }
}

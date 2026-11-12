<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function copy;
use function file_put_contents;
use function filesize;
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
    public static function validationOptionsProvider(): array
    {
        return [
            'max size' => ['options' => ['maxSize' => 500]],
            'allowed types' => ['options' => ['allowedTypes' => ['image/png']]],
            'allowed extensions' => ['options' => ['allowedExtensions' => ['png']]],
        ];
    }

    /** @param ValidationOptions $options */
    #[DataProvider('validationOptionsProvider')]
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

    public function testValidationAllowedTypesChecksFileContent(): void
    {
        // The client claims image/jpeg, but the uploaded bytes are PHP source.
        $tmpFile = (string) tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tmpFile, "<?php\n// not an image\n");

        /** @var UploadedFile */
        $fileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => (int) filesize($tmpFile),
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
        ];
        /** @var ValidationOptions */
        $options = ['allowedTypes' => ['image/jpeg']];

        $upload = FileUpload::create($fileData, $options);
        @unlink($tmpFile);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertStringContainsString('type text/x-php is not allowed', (string) $upload->message);
    }

    public function testValidationAllowedTypesAcceptsMatchingContent(): void
    {
        $tmpFile = (string) tempnam(sys_get_temp_dir(), 'upload_test');
        copy(__DIR__ . '/Example/fixtures/test-image.jpg', $tmpFile);

        /** @var UploadedFile */
        $fileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => (int) filesize($tmpFile),
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
        ];
        /** @var ValidationOptions */
        $options = ['allowedTypes' => ['image/jpeg']];

        $upload = FileUpload::create($fileData, $options);
        @unlink($tmpFile);

        $this->assertInstanceOf(FileUpload::class, $upload);
    }

    public function testValidationAllowedTypesWithMissingTempFile(): void
    {
        /** @var ValidationOptions */
        $options = ['allowedTypes' => ['image/jpeg']];

        // validFileData points at a tmp_name that does not exist
        $upload = FileUpload::create($this->validFileData, $options);

        $this->assertInstanceOf(ErrorFileUpload::class, $upload);
        $this->assertStringContainsString('Unable to detect the file type', (string) $upload->message);
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

    public function testToArray(): void
    {
        /** @var UploadedFile */
        $expected = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/test',
            'error' => UPLOAD_ERR_OK,
        ];

        $upload = FileUpload::create($expected);
        $this->assertInstanceOf(FileUpload::class, $upload);

        $actual = $upload->toArray();
        $this->assertEquals($expected, $actual);

        // Check that it works in the same way in error cases
        $errorUpload = new ErrorFileUpload($expected, 'Test error');
        $this->assertEquals($expected, $errorUpload->toArray());
    }
}

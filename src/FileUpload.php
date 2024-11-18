<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use Koriym\FileUpload\Exception\FileNotFoundException;
use Koriym\FileUpload\Exception\MimeTypeException;
use Koriym\FileUpload\Exception\TempFileException;

use function assert;
use function copy;
use function file_exists;
use function filesize;
use function in_array;
use function is_string;
use function mime_content_type;
use function move_uploaded_file;
use function pathinfo;
use function rename;
use function sprintf;
use function str_starts_with;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const PATHINFO_BASENAME;
use const PATHINFO_EXTENSION;
use const PHP_SAPI;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

/**
 * @psalm-import-type UploadedFile from AbstractFileUpload
 * @psalm-import-type ValidationOptions from AbstractFileUpload
 * @psalm-immutable
 */
class FileUpload extends AbstractFileUpload
{
    /** @param UploadedFile $fileData */
    private function __construct(
        array $fileData,
    ) {
        parent::__construct($fileData);
    }

    /**
     * @param array<string, mixed> $fileData
     * @param ValidationOptions    $validationOptions
     */
    public static function create(
        array $fileData,
        array $validationOptions = [],
    ): self|ErrorFileUpload {
        if (! isset($fileData['name'], $fileData['type'], $fileData['size'], $fileData['tmp_name'], $fileData['error'])) {
            /** @var UploadedFile */
            $defaultData = [
                'name' => $fileData['name'] ?? '',
                'type' => '',
                'size' => 0,
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ];

            return new ErrorFileUpload($defaultData, 'Invalid file data structure');
        }

        assert(is_string($fileData['type']));
        /** @var UploadedFile $fileData */

        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return new ErrorFileUpload($fileData);
        }

        if (isset($validationOptions['maxSize']) && $fileData['size'] > $validationOptions['maxSize']) {
            return new ErrorFileUpload(
                $fileData,
                sprintf('File size exceeds maximum allowed size of %d bytes', $validationOptions['maxSize']),
            );
        }

        if (
            isset($validationOptions['allowedTypes'])
            && ! in_array($fileData['type'], $validationOptions['allowedTypes'], true)
        ) {
            return new ErrorFileUpload(
                $fileData,
                sprintf('File type %s is not allowed', $fileData['type']),
            );
        }

        /** @var UploadedFile $fileData */
        assert(is_string($fileData['name']));
        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        if (
            isset($validationOptions['allowedExtensions'])
            && ! in_array($extension, $validationOptions['allowedExtensions'], true)
        ) {
            return new ErrorFileUpload(
                $fileData,
                sprintf('File extension %s is not allowed', $extension),
            );
        }

        return new self($fileData);
    }

    /**
     * Move uploaded file to new destination
     *
     * Use move_uploaded_file() in web environment for security.
     * In CLI environment (testing), use rename() instead.
     *
     * @see https://www.php.net/manual/function.move-uploaded-file.php
     * @see https://www.php.net/manual/function.rename.php
     * @psalm-suppress ImpureFunctionCall
     */
    public function move(string $destination): bool
    {
        if (PHP_SAPI === 'cli') {
            return rename($this->tmpName, $destination);
        }

        return move_uploaded_file($this->tmpName, $destination); // @codeCoverageIgnore
    }

    public function isImage(): bool
    {
        return str_starts_with($this->type, 'image/');
    }

    /**
     * Create a FileUpload instance from an actual file for testing
     *
     * @param string            $filepath          Path to the source file
     * @param ValidationOptions $validationOptions Optional validation options
     *
     * @throws FileNotFoundException
     * @throws MimeTypeException
     * @throws TempFileException
     */
    public static function fromFile(
        string $filepath,
        array $validationOptions = [],
    ): self|ErrorFileUpload {
        if (! file_exists($filepath)) {
            throw new FileNotFoundException($filepath);
        }

        $mimeType = mime_content_type($filepath);
        if ($mimeType === false) {
            throw new MimeTypeException($filepath); // @codeCoverageIgnore
        }

        $size = filesize($filepath);

        $tmpName = tempnam(sys_get_temp_dir(), 'upload_test');
        if ($tmpName === false) {
            throw new TempFileException($filepath); // @codeCoverageIgnore
        }

        if (! copy($filepath, $tmpName)) {
            // @codeCoverageIgnoreStart
            unlink($tmpName);

            throw new TempFileException($filepath);
            // @codeCoverageIgnoreEnd
        }

        /** @var UploadedFile $fileData */
        $fileData = [
            'name' => pathinfo($filepath, PATHINFO_BASENAME),
            'type' => $mimeType,
            'size' => $size,
            'tmp_name' => $tmpName,
            'error' => UPLOAD_ERR_OK,
        ];

        return self::create($fileData, $validationOptions);
    }
}

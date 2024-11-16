<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use function in_array;
use function move_uploaded_file;
use function pathinfo;
use function sprintf;
use function str_starts_with;

use const PATHINFO_EXTENSION;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

/**
 * @psalm-import-type UploadedFile from AbstractFileUpload
 * @psalm-import-type ValidationOptions from AbstractFileUpload
 * @psalm-immutable
 */
class FileUpload extends AbstractFileUpload
{
    /**
     * @param UploadedFile      $fileData
     * @param ValidationOptions $validationOptions
     */
    private function __construct(
        array $fileData,
        private array $validationOptions = [],
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

        return new self($fileData, $validationOptions);
    }

    /** @psalm-external-mutation-free */
    public function move(string $destination): bool
    {
        return move_uploaded_file($this->tmpName, $destination);
    }

    /** @psalm-pure */
    public function isImage(): bool
    {
        return str_starts_with($this->type, 'image/');
    }
}

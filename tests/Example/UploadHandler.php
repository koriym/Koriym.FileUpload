<?php

declare(strict_types=1);

namespace Koriym\FileUpload\Example;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;

use function basename;
use function is_dir;
use function mkdir;

/**
 * Example upload handler implementation
 *
 * This is a reference implementation showing how to use FileUpload in your application.
 */
final class UploadHandler
{
    /**
     * @param string       $uploadDirectory Upload destination directory
     * @param positive-int $maxSize         Maximum file size in bytes
     * @param list<string> $allowedTypes    Allowed MIME types
     */
    public function __construct(
        private readonly string $uploadDirectory,
        private readonly int $maxSize = 5 * 1024 * 1024, // 5MB
        private readonly array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'],
    ) {
    }

    /** @param array<string, mixed> $fileData $_FILES array element */
    public function handleUpload(array $fileData): UploadResult
    {
        $upload = FileUpload::create($fileData, [
            'maxSize' => $this->maxSize,
            'allowedTypes' => $this->allowedTypes,
        ]);

        if ($upload instanceof ErrorFileUpload) {
            throw new UploadException($upload->message ?? 'Upload failed');
        }

        // Ensure upload directory exists
        if (! is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }

        // Use original filename (in production, you should sanitize this)
        $destination = $this->uploadDirectory . '/' . basename($upload->name);

        if (! $upload->move($destination)) {
            throw new UploadException('Failed to move uploaded file');
        }

        return new UploadResult(
            path: $destination,
            originalName: $upload->name,
            mimeType: $upload->type,
            size: $upload->size,
        );
    }
}

<?php

declare(strict_types=1);

namespace Koriym\FileUpload\Example;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;

use function bin2hex;
use function is_dir;
use function mkdir;
use function random_bytes;
use function strtolower;

/**
 * Example upload handler implementation
 *
 * This is a reference implementation showing how to use FileUpload in your application.
 * It is safe to copy as a starting point:
 *
 * - `allowedExtensions` is set together with `allowedTypes`, so a file named `*.php`
 *   is rejected even if its content looks like an image.
 * - The stored filename is generated on the server. The client-supplied name is never
 *   used as a path, which also prevents directory traversal and overwrites.
 */
final class UploadHandler
{
    /**
     * @param string       $uploadDirectory   Upload destination directory
     * @param positive-int $maxSize           Maximum file size in bytes
     * @param list<string> $allowedTypes      Allowed MIME types
     * @param list<string> $allowedExtensions Allowed file extensions
     */
    public function __construct(
        private readonly string $uploadDirectory,
        private readonly int $maxSize = 5 * 1024 * 1024, // 5MB
        private readonly array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'],
        private readonly array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'],
    ) {
    }

    /** @param array<string, mixed> $fileData $_FILES array element */
    public function handleUpload(array $fileData): UploadResult
    {
        $upload = FileUpload::create($fileData, [
            'maxSize' => $this->maxSize,
            'allowedTypes' => $this->allowedTypes,
            'allowedExtensions' => $this->allowedExtensions,
        ]);

        if ($upload instanceof ErrorFileUpload) {
            throw new UploadException($upload->message ?? 'Upload failed');
        }

        // Ensure upload directory exists
        if (! is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }

        // Generate the stored filename on the server. The extension has already been
        // checked against allowedExtensions, so only an allowed extension is used.
        $filename = bin2hex(random_bytes(16)) . '.' . strtolower((string) $upload->extension);
        $destination = $this->uploadDirectory . '/' . $filename;

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

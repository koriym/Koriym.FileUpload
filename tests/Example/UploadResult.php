<?php

declare(strict_types=1);

namespace Koriym\FileUpload\Example;

final class UploadResult
{
    public function __construct(
        public readonly string $path,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $size,
    ) {
    }
}

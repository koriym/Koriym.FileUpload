<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use function pathinfo;

use const PATHINFO_EXTENSION;

/**
 * @psalm-type UploadedFile = array{
 *   name: string,
 *   type: string,
 *   size: int,
 *   tmp_name: string,
 *   error: int
 * }
 * @psalm-type ValidationOptions = array{
 *   maxSize?: positive-int,
 *   allowedTypes?: list<string>,
 *   allowedExtensions?: list<string>
 * }
 * @psalm-immutable
 */
abstract class AbstractFileUpload
{
    public string $name;
    public string $type;
    public int $size;
    public string $tmpName;
    public int $error;
    public string|null $extension;

    /** @param UploadedFile $fileData */
    protected function __construct(array $fileData)
    {
        $this->name = $fileData['name'];
        $this->type = $fileData['type'];
        $this->size = $fileData['size'];
        $this->tmpName = $fileData['tmp_name'];
        $this->error = $fileData['error'];
        $this->extension = pathinfo($this->name, PATHINFO_EXTENSION);
    }
}

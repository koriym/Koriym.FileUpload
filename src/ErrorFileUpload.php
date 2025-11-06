<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_PARTIAL;

/**
 * @psalm-import-type UploadedFile from AbstractFileUpload
 * @psalm-immutable
 */
final class ErrorFileUpload extends AbstractFileUpload
{
    public string|null $message;

    private const ERROR_MESSAGES = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
    ];

    /** @param UploadedFile $fileData */
    public function __construct(
        array $fileData,
        string|null $message = null,
    ) {
        parent::__construct($fileData);

        $this->message = $message ?? self::ERROR_MESSAGES[$this->error] ?? null;
    }
}

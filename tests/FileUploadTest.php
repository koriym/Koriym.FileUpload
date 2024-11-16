<?php

declare(strict_types=1);

namespace Koriym\FileUpload;

use PHPUnit\Framework\TestCase;

class FileUploadTest extends TestCase
{
    protected FileUpload $fileUpload;

    protected function setUp(): void
    {
        $this->fileUpload = new FileUpload();
    }

    public function testIsInstanceOfFileUpload(): void
    {
        $actual = $this->fileUpload;
        $this->assertInstanceOf(FileUpload::class, $actual);
    }
}

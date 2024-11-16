# FileUpload

Immutable file upload value object

## Installation

```bash
composer require vendor/file-upload
```

## Usage

```php
$upload = FileUpload::create($_FILES['upload'], [
    'maxSize' => 5 * 1024 * 1024,
    'allowedTypes' => ['image/jpeg', 'image/png'],
    'allowedExtensions' => ['jpg', 'jpeg', 'png']
]);

match (true) {
    $upload instanceof FileUpload => $upload->move('./uploads/' . $upload->name)
        ? 'Upload successful'
        : 'Failed to move file',
    $upload instanceof ErrorFileUpload => 'Error: ' . $upload->message,
};
```

## Properties

Both `FileUpload` and `ErrorFileUpload` have the following properties:

```php
public string $name;        // Original filename
public string $type;        // MIME type
public int $size;          // File size in bytes
public string $tmpName;    // Temporary file path
public int $error;         // PHP upload error code
public ?string $extension; // File extension
```

Additionally, `ErrorFileUpload` has:

```php
public ?string $message;   // Error message
```

## Validation Options

You can pass the following validation options to `create()`:

- `maxSize`: Maximum file size in bytes
- `allowedTypes`: Array of allowed MIME types
- `allowedExtensions`: Array of allowed file extensions

## Testing

The `move()` method behaves differently in CLI and web environments:
- In web environment: Uses `move_uploaded_file()` for security
- In CLI environment (testing): Uses `rename()` for testability

```php
// Test environment
$upload = FileUpload::create([
    'name' => 'test.jpg',
    'type' => 'image/jpeg',
    'size' => 1024,
    'tmp_name' => '/path/to/temp/file',
    'error' => UPLOAD_ERR_OK
]);

$result = $upload->move('./uploads/test.jpg');
```

## Requirements

- PHP 8.1+

## License

MIT

## Additional Information

PHP's `$_FILES` structure:
```php
$_FILES['upload'] = [
    'name'      => 'profile.jpg',      // Original filename
    'type'      => 'image/jpeg',       // MIME type
    'size'      => 12345,              // File size in bytes
    'tmp_name'  => '/tmp/phpxxxxx',    // Temporary file path
    'error'     => 0                   // Error code (0 means success)
];
```

For multiple file uploads:
```html
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="images[]" multiple>
</form>
```

```php
$_FILES['images'] = [
    'name'     => ['image1.jpg', 'image2.png'],
    'type'     => ['image/jpeg', 'image/png'],
    'size'     => [12345, 67890],
    'tmp_name' => ['/tmp/phpxxxxx', '/tmp/phpyyyyy'],
    'error'    => [0, 0]
];
```

PHP Upload Error Codes:
```php
UPLOAD_ERR_OK         // 0: Success
UPLOAD_ERR_INI_SIZE   // 1: Exceeds upload_max_filesize in php.ini
UPLOAD_ERR_FORM_SIZE  // 2: Exceeds MAX_FILE_SIZE in HTML form
UPLOAD_ERR_PARTIAL    // 3: Partially uploaded
UPLOAD_ERR_NO_FILE    // 4: No file uploaded
UPLOAD_ERR_NO_TMP_DIR // 6: Missing temporary folder
UPLOAD_ERR_CANT_WRITE // 7: Failed to write to disk
UPLOAD_ERR_EXTENSION  // 8: Stopped by PHP extension
```

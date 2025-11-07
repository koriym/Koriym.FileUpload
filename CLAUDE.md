# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Koriym.FileUpload is a type-safe PHP library for handling file uploads using immutable value objects. It transforms PHP's native `$_FILES` array into type-safe objects with early validation, making both production code and testing simpler and more reliable.

## Development Commands

### Testing
- `composer test` - Run PHPUnit tests
- `composer coverage` - Generate coverage report with Xdebug
- `composer pcov` - Generate coverage report with PCOV (faster)
- `composer phpdbg` - Generate coverage report with phpdbg

### Code Quality
- `composer cs` - Check coding standards (PHP_CodeSniffer)
- `composer cs-fix` - Auto-fix coding standards violations
- `composer sa` - Run static analysis (PHPStan + Psalm)
- `composer tests` - Run cs + sa + test (full quality check)
- `composer build` - Run clean + cs + sa + pcov + metrics (complete build)

### Utilities
- `composer clean` - Clear PHPStan and Psalm caches
- `composer metrics` - Generate code metrics report

### Before Commits/PRs
Always run `composer cs-fix` before committing and `composer tests` before creating a pull request.

## Architecture

### Core Classes

**AbstractFileUpload** (`src/AbstractFileUpload.php`)
- Base class defining the common structure for all file upload objects
- Contains public properties: `name`, `type`, `size`, `tmpName`, `error`, `extension`
- Provides `toArray()` method to convert back to `$_FILES` format
- Uses Psalm type definitions for `UploadedFile` and `ValidationOptions`

**FileUpload** (`src/FileUpload.php`)
- Represents a successful file upload with validation
- Factory method `create(array $fileData, array $validationOptions)` - Creates instance from `$_FILES` data
- Factory method `fromFile(string $filepath, array $validationOptions)` - Creates instance from file path (for testing)
- `move(string $destination)` - Moves uploaded file (uses `move_uploaded_file()` in web, `rename()` in CLI)
- `isImage()` - Checks if file is an image type
- Returns `ErrorFileUpload` when validation fails or upload has errors

**ErrorFileUpload** (`src/ErrorFileUpload.php`)
- Represents a failed upload with error information
- Additional property: `message` (human-readable error message)
- Automatically maps PHP upload error codes to descriptive messages

### Validation Options

Both `FileUpload::create()` and `FileUpload::fromFile()` accept validation options:
- `maxSize` - Maximum file size in bytes (positive-int)
- `allowedTypes` - Array of allowed MIME types (list<string>)
- `allowedExtensions` - Array of allowed file extensions (list<string>)

### Return Type Pattern

The factory methods use a union return type pattern:
```php
public static function create(...): self|ErrorFileUpload
```

This allows consumers to use type-safe pattern matching:
```php
match (true) {
    $upload instanceof FileUpload => /* handle success */,
    $upload instanceof ErrorFileUpload => /* handle error */,
};
```

## Testing Strategy

### Testing File Upload Handlers

The library provides two approaches for testing:

1. **Using `toArray()`** - Create FileUpload from array, convert back to test stubs
2. **Using `fromFile()`** - Create FileUpload directly from test fixture files (recommended for realistic testing)

Place test files in `tests/fixtures/` directory and use `fromFile()` to create test instances:
```php
$upload = FileUpload::fromFile(__DIR__ . '/fixtures/test-image.jpg');
```

The `move()` method automatically adapts to the environment:
- Web (non-CLI): Uses `move_uploaded_file()` for security
- CLI (testing): Uses `rename()` for testability

## PHP Version and Dependencies

- **Minimum PHP**: 8.2
- **Required extension**: ext-fileinfo
- **Test framework**: PHPUnit 11
- **Static analysis**: PHPStan 2.x, Psalm 6.x
- **Coding standards**: PHP_CodeSniffer with Doctrine and Slevomat standards

## CI/CD

The project uses GitHub Actions with a reusable workflow from `ray-di/.github`. Tests run on:
- PHP 8.2 (lowest supported)
- PHP 8.3, 8.4 (old stable)
- PHP 8.5 (current stable)

## Code Style

- Follows Doctrine Coding Standard
- Strict types enabled (`declare(strict_types=1)`)
- Immutable value objects (marked with `@psalm-immutable`)
- Type safety enforced via Psalm and PHPStan at strict levels

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageProcessingService
{
    /**
     * Get maximum width for resized images from config.
     */
    private function getMaxWidth(): int
    {
        return (int) config('app.image.max_width', 1280);
    }

    /**
     * Get maximum height for resized images from config.
     */
    private function getMaxHeight(): int
    {
        return (int) config('app.image.max_height', 1280);
    }

    /**
     * Get JPEG quality from config.
     */
    private function getJpegQuality(): int
    {
        return (int) config('app.image.jpeg_quality', 85);
    }

    /**
     * Process and store image with resizing and compression.
     * Images are organized by date and branch name.
     * 
     * @param UploadedFile $file
     * @param string $baseDirectory Base directory (e.g., 'transactions/items')
     * @param string|null $branchName Branch name for organizing files (will be sanitized)
     * @return string The stored file path relative to storage/app/private
     */
    public function processAndStore(UploadedFile $file, string $baseDirectory, ?string $branchName = null): string
    {
        // Check if GD extension is loaded
        if (!\extension_loaded('gd')) {
            throw new \Exception('GD extension is not loaded. Please install php-gd extension.');
        }

        $filePath = $file->getRealPath();
        
        // Validate file exists and is readable
        if (!\file_exists($filePath)) {
            throw new \Exception('Uploaded file does not exist');
        }

        if (!\is_readable($filePath)) {
            throw new \Exception('Uploaded file is not readable');
        }

        // Get image info
        $imageInfo = @\getimagesize($filePath);
        if (!$imageInfo) {
            // Try to get more info about why it failed
            $fileSize = \filesize($filePath);
            $mimeType = $file->getMimeType();
            throw new \Exception(
                "Invalid or corrupted image file. " .
                "File size: {$fileSize} bytes, " .
                "MIME type: {$mimeType}, " .
                "File path: {$filePath}"
            );
        }

        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Get max dimensions from config
        $maxWidth = $this->getMaxWidth();
        $maxHeight = $this->getMaxHeight();

        // Calculate new dimensions (maintain aspect ratio)
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int) ($originalWidth * $ratio);
        $newHeight = (int) ($originalHeight * $ratio);

        // If image is smaller than max, use original dimensions
        if ($ratio >= 1) {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        // Validate dimensions
        if ($newWidth <= 0 || $newHeight <= 0) {
            throw new \Exception("Invalid image dimensions: {$newWidth}x{$newHeight}");
        }

        // Create image resource based on MIME type
        $sourceImage = $this->createImageResource($file->getRealPath(), $mimeType);

        // Create new image with calculated dimensions
        $newImage = @\imagecreatetruecolor($newWidth, $newHeight);
        if ($newImage === false) {
            \imagedestroy($sourceImage);
            throw new \Exception("Failed to create new image resource with dimensions {$newWidth}x{$newHeight}");
        }

        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            \imagealphablending($newImage, false);
            \imagesavealpha($newImage, true);
            $transparent = \imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            if ($transparent !== false) {
                \imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
        } else {
            // For JPEG, fill with white background
            $white = \imagecolorallocate($newImage, 255, 255, 255);
            if ($white !== false) {
                \imagefill($newImage, 0, 0, $white);
            }
        }

        // Resize image
        $resizeResult = @\imagecopyresampled(
            $newImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        if ($resizeResult === false) {
            \imagedestroy($sourceImage);
            \imagedestroy($newImage);
            throw new \Exception("Failed to resize image");
        }

        // Build directory path: baseDirectory/YYYY-MM-DD/{branch-name}/
        $date = now()->format('Y-m-d');
        $directory = $baseDirectory . '/' . $date;
        if ($branchName) {
            // Sanitize branch name for filesystem (remove special chars, spaces to hyphens)
            $sanitizedBranchName = $this->sanitizeBranchName($branchName);
            $directory .= '/' . $sanitizedBranchName;
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.jpg';
        $path = $directory . '/' . $filename;
        $fullPath = storage_path('app/private/' . $path);

        // Ensure directory exists
        $dir = \dirname($fullPath);
        if (!\is_dir($dir)) {
            \mkdir($dir, 0755, true);
        }

        // Save as JPEG (always convert to JPEG for consistency and smaller file size)
        \imagejpeg($newImage, $fullPath, $this->getJpegQuality());

        // Clean up memory
        \imagedestroy($sourceImage);
        \imagedestroy($newImage);

        return $path;
    }

    /**
     * Create image resource from file based on MIME type.
     */
    private function createImageResource(string $filePath, string $mimeType)
    {
        if (!\file_exists($filePath)) {
            throw new \Exception('Image file does not exist: ' . $filePath);
        }

        if (!\is_readable($filePath)) {
            throw new \Exception('Image file is not readable: ' . $filePath);
        }

        // Check if GD extension is loaded
        if (!\extension_loaded('gd')) {
            throw new \Exception('GD extension is not loaded. Please install php-gd extension on the server.');
        }

        // Check if GD functions are available
        if (!\function_exists('imagecreatefromjpeg')) {
            throw new \Exception('GD image functions are not available. Please install php-gd extension. Run: apt-get install php-gd (or yum install php-gd) and restart PHP-FPM/web server.');
        }

        // Use call_user_func to ensure functions are called from global namespace
        $resource = null;
        if ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') {
            $resource = @call_user_func('imagecreatefromjpeg', $filePath);
        } elseif ($mimeType === 'image/png') {
            $resource = @call_user_func('imagecreatefrompng', $filePath);
        } elseif ($mimeType === 'image/gif') {
            $resource = @call_user_func('imagecreatefromgif', $filePath);
        } elseif ($mimeType === 'image/webp') {
            $resource = @call_user_func('imagecreatefromwebp', $filePath);
        }

        if ($resource === false || $resource === null) {
            // Try to detect the actual image type from file content
            $actualMimeType = \mime_content_type($filePath);
            if ($actualMimeType && $actualMimeType !== $mimeType) {
                // Retry with actual MIME type
                if ($actualMimeType === 'image/jpeg' || $actualMimeType === 'image/jpg') {
                    $resource = @call_user_func('imagecreatefromjpeg', $filePath);
                } elseif ($actualMimeType === 'image/png') {
                    $resource = @call_user_func('imagecreatefrompng', $filePath);
                } elseif ($actualMimeType === 'image/gif') {
                    $resource = @call_user_func('imagecreatefromgif', $filePath);
                } elseif ($actualMimeType === 'image/webp') {
                    $resource = @call_user_func('imagecreatefromwebp', $filePath);
                }
            }

            if ($resource === false || $resource === null) {
                $error = \error_get_last();
                $errorMsg = $error ? $error['message'] : 'Unknown error';
                throw new \Exception(
                    "Failed to create image resource from file. " .
                    "MIME type: {$mimeType}, " .
                    "File size: " . \filesize($filePath) . " bytes, " .
                    "Error: {$errorMsg}"
                );
            }
        }

        return $resource;
    }

    /**
     * Sanitize branch name for use in filesystem paths.
     * Converts to lowercase, replaces spaces with hyphens, removes special characters.
     */
    private function sanitizeBranchName(string $branchName): string
    {
        // Convert to lowercase
        $sanitized = strtolower($branchName);
        // Replace spaces and underscores with hyphens
        $sanitized = preg_replace('/[\s_]+/', '-', $sanitized);
        // Remove special characters, keep only alphanumeric and hyphens
        $sanitized = preg_replace('/[^a-z0-9\-]/', '', $sanitized);
        // Remove multiple consecutive hyphens
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        // Remove leading/trailing hyphens
        $sanitized = trim($sanitized, '-');
        
        return $sanitized ?: 'unknown';
    }
}


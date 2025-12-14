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
        // Get image info
        $imageInfo = getimagesize($file->getRealPath());
        if (!$imageInfo) {
            throw new \Exception('Invalid image file');
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

        // Create image resource based on MIME type
        $sourceImage = $this->createImageResource($file->getRealPath(), $mimeType);

        // Create new image with calculated dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled(
            $newImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

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
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Save as JPEG (always convert to JPEG for consistency and smaller file size)
        imagejpeg($newImage, $fullPath, $this->getJpegQuality());

        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $path;
    }

    /**
     * Create image resource from file based on MIME type.
     */
    private function createImageResource(string $filePath, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($filePath),
            'image/png' => imagecreatefrompng($filePath),
            'image/gif' => imagecreatefromgif($filePath),
            'image/webp' => imagecreatefromwebp($filePath),
            default => throw new \Exception('Unsupported image type: ' . $mimeType),
        };
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


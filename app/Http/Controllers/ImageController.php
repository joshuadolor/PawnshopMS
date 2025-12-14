<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller
{
    /**
     * Serve an image file with authentication.
     * 
     * @param Request $request
     * @param string $path The image path (e.g., 'transactions/items/2025-12-15/branch-name/filename.jpg')
     * @return Response|BinaryFileResponse
     */
    public function show(Request $request, string $path): Response|BinaryFileResponse
    {
        // Ensure user is authenticated
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        // Sanitize the path to prevent directory traversal
        $path = str_replace('..', '', $path);
        $path = ltrim($path, '/');

        // Check if file exists in private storage
        $fullPath = storage_path('app/private/' . $path);
        
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            abort(404, 'Image not found');
        }

        // Get MIME type
        $mimeType = mime_content_type($fullPath);
        if (!$mimeType || !str_starts_with($mimeType, 'image/')) {
            abort(404, 'Invalid image file');
        }

        // Return the file with appropriate headers
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}

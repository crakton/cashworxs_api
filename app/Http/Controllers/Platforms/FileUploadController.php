<?php

namespace App\Http\Controllers\Platforms;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Exception;

class FileUploadController extends BaseController
{
    protected $cloudinary;

    public function __construct()
    {
        // Initialize Cloudinary with configuration
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    /**
     * Handle file upload to Cloudinary
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
        ]);

        try {
            // Verify Cloudinary configuration
            $this->verifyCloudinaryConfig();

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $filename = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $mimeType = $file->getMimeType();

            // Determine resource type based on mime type
            $resourceType = $this->getResourceType($mimeType);

            // Prepare upload options
            $options = [
                'public_id' => 'uploads/' . $filename,
                'resource_type' => $resourceType,
                'use_filename' => false,
                'unique_filename' => true,
            ];

            // Add image-specific transformations
            if ($resourceType === 'image') {
                $options['transformation'] = [
                    ['width' => 2000, 'height' => 2000, 'crop' => 'limit'],
                    ['quality' => 'auto:good']
                ];
            }

            // Upload the file
            $uploadResult = $this->cloudinary->uploadApi()->upload($file->getRealPath(), $options);

            return $this->sendResponse([
                'public_id' => $uploadResult['public_id'],
                'url' => $uploadResult['secure_url'],
                'file_type' => $mimeType,
                'size' => $uploadResult['bytes'],
                'original_name' => $originalName,
                'width' => $uploadResult['width'] ?? null,
                'height' => $uploadResult['height'] ?? null,
                'format' => $uploadResult['format'] ?? null,
            ], 'File uploaded successfully');

        } catch (Exception $e) {
            return $this->sendError([], 'File upload failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a file from Cloudinary
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $request->validate([
            'public_id' => 'required|string',
        ]);

        try {
            $this->verifyCloudinaryConfig();
            
            $result = $this->cloudinary->uploadApi()->destroy($request->public_id);
            
            // Check if deletion was successful
            if ($result['result'] !== 'ok') {
                return $this->sendError([], 'File deletion failed: ' . $result['result'], 404);
            }
            
            return $this->sendResponse($result, 'File deleted successfully');

        } catch (Exception $e) {
            return $this->sendError([], 'File deletion failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List files in a specific folder or all files
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $request->validate([
            'folder' => 'sometimes|string',
            'max_results' => 'sometimes|integer|min:1|max:500'
        ]);

        try {
            $this->verifyCloudinaryConfig();

            $options = [
                'max_results' => $request->input('max_results', 10),
                'type' => 'upload'
            ];

            if ($request->has('folder')) {
                $options['prefix'] = $request->folder . '/';
            } else {
                $options['prefix'] = 'uploads/';
            }

            $result = $this->cloudinary->adminApi()->assets($options);

            return $this->sendResponse([
                'resources' => $result['resources'],
                'total_count' => count($result['resources']),
                'next_cursor' => $result['next_cursor'] ?? null,
            ], 'Files listed successfully');

        } catch (Exception $e) {
            return $this->sendError([], 'Failed to list files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get file details by public_id
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function details(Request $request)
    {
        $request->validate([
            'public_id' => 'required|string',
        ]);

        try {
            $this->verifyCloudinaryConfig();
            
            $result = $this->cloudinary->adminApi()->asset($request->public_id);
            
            return $this->sendResponse($result, 'File details retrieved successfully');

        } catch (Exception $e) {
            return $this->sendError([], 'Failed to get file details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify Cloudinary configuration
     */
    protected function verifyCloudinaryConfig()
    {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        if (empty($cloudName)) {
            throw new Exception("Cloudinary cloud_name is not configured. Please set CLOUDINARY_CLOUD_NAME in your .env file");
        }
        
        if (empty($apiKey)) {
            throw new Exception("Cloudinary api_key is not configured. Please set CLOUDINARY_API_KEY in your .env file");
        }
        
        if (empty($apiSecret)) {
            throw new Exception("Cloudinary api_secret is not configured. Please set CLOUDINARY_API_SECRET in your .env file");
        }
    }

    /**
     * Determine resource type based on mime type
     */
    protected function getResourceType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        return 'auto';
    }

    /**
     * Test Cloudinary connection
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function test()
    {
        try {
            $this->verifyCloudinaryConfig();
            
            // Try to ping Cloudinary
            $result = $this->cloudinary->adminApi()->ping();
            
            return $this->sendResponse([
                'ping' => $result,
                'config' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key' => env('CLOUDINARY_API_KEY') ? 'Set' : 'Not Set',
                    'api_secret' => env('CLOUDINARY_API_SECRET') ? 'Set' : 'Not Set',
                ]
            ], 'Cloudinary connection successful');

        } catch (Exception $e) {
            return $this->sendError([], 'Cloudinary connection failed: ' . $e->getMessage(), 500);
        }
    }
}
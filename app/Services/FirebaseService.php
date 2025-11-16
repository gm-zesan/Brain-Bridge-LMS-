<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Storage;
use Exception;

class FirebaseService
{
    protected Database $database;
    protected Messaging $messaging;
    protected Auth $auth;
    protected Storage $storage;

    // public function __construct()
    // {
    //     $factory = (new Factory)
    //         ->withServiceAccount(storage_path('app/firebase/brainbridge-storage-firebase-adminsdk.json'));
    //     $this->auth = $factory->createAuth();
    //     $this->storage = $factory->createStorage();
    // }
    public function __construct()
    {
        try {
            // Get the path to service account file
            $serviceAccountPath = storage_path('app/firebase/brainbridge-storage-firebase-adminsdk.json');
            
            // Check if file exists
            if (!file_exists($serviceAccountPath)) {
                throw new Exception("Firebase service account file not found at: {$serviceAccountPath}");
            }

            // Check if file is readable
            if (!is_readable($serviceAccountPath)) {
                throw new Exception("Firebase service account file is not readable. Check file permissions.");
            }

            // Validate JSON content
            $jsonContent = file_get_contents($serviceAccountPath);
            $jsonData = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON in Firebase service account file: " . json_last_error_msg());
            }

            // Check required fields
            $requiredFields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email'];
            foreach ($requiredFields as $field) {
                if (!isset($jsonData[$field])) {
                    throw new Exception("Missing required field '{$field}' in Firebase service account file");
                }
            }

            // Initialize Firebase
            $factory = (new Factory)
                ->withServiceAccount($serviceAccountPath);

            $this->auth = $factory->createAuth();
            $this->storage = $factory->createStorage();

            Log::info('Firebase service initialized successfully');

        } catch (Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage(), [
                'file' => $serviceAccountPath ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception('Firebase service initialization failed: ' . $e->getMessage());
        }
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }

    public function getStorage(): Storage
    {
        return $this->storage;
    }

    /**
     * Upload course video with validation
     */
    public function uploadCourseVideo($file, string $courseId, array $options = []): array
    {
        try {
            // Validate file
            $maxSize = $options['maxSize'] ?? 500 * 1024 * 1024; // 500MB default
            $allowedMimes = $options['allowedMimes'] ?? ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'];

            if ($file->getSize() > $maxSize) {
                return [
                    'success' => false,
                    'error' => 'File size exceeds maximum allowed size'
                ];
            }

            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return [
                    'success' => false,
                    'error' => 'Invalid file type. Only video files are allowed'
                ];
            }

            $bucket = $this->storage->getBucket();
            $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $filePath = "courses/{$courseId}/videos/{$fileName}";

            // Upload file with metadata
            $bucket->upload(
                fopen($file->getRealPath(), 'r'),
                [
                    'name' => $filePath,
                    'metadata' => [
                        'contentType' => $file->getMimeType(),
                        'metadata' => [
                            'courseId' => $courseId,
                            'originalName' => $file->getClientOriginalName(),
                            'uploadedAt' => now()->toIso8601String(),
                            'fileSize' => $file->getSize(),
                        ]
                    ]
                ]
            );

            return [
                'success' => true,
                'path' => $filePath,
                'filename' => $fileName,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(string $filePath): array
    {
        try {
            $bucket = $this->storage->getBucket();
            $object = $bucket->object($filePath);
            
            if (!$object->exists()) {
                return [
                    'success' => false,
                    'error' => 'File not found'
                ];
            }

            $object->delete();
            
            return [
                'success' => true,
                'message' => 'File deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

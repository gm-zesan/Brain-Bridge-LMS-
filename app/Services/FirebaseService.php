<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Storage;

class FirebaseService
{
    protected Database $database;
    protected Messaging $messaging;
    protected Auth $auth;
    protected Storage $storage;

    public function __construct()
    {
        $factory = (new Factory)
            //Path to service account file
            ->withServiceAccount(storage_path('app/firebase/brainbridge-storage-firebase-adminsdk.json'));
        $this->auth = $factory->createAuth();
        $this->storage = $factory->createStorage();
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

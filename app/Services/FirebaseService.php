<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Storage;
use Exception;
use Google\Cloud\Storage\Bucket;

class FirebaseService
{
    // protected Auth $auth;
    // protected Storage $storage;

    protected $database = null;
    protected $messaging = null;
    protected $auth = null;
    protected $storage = null;
    protected $bucket = null;
    protected $initialized = false;
    protected $error = null;

    public function __construct()
    {
        $serviceAccountPath = storage_path('app/firebase/brainbridge-storage-firebase-adminsdk.json');
        
        if (!file_exists($serviceAccountPath)) {
            throw new Exception("Firebase service account file not found at: {$serviceAccountPath}");
        }

        $factory = (new Factory)
            ->withServiceAccount($serviceAccountPath);

        $this->auth = $factory->createAuth();

        $bucketName = config('services.firebase.storage_bucket', 'brainbridge-storage.firebasestorage.app');

        try {
            $this->storage = $factory->createStorage();
            $this->bucket = $this->storage->getBucket($bucketName);
            
            // Test bucket access
            if (!$this->bucket->exists()) {
                throw new Exception("Bucket does not exist: {$bucketName}");
            }
            
            Log::info('Firebase Storage bucket initialized successfully', [
                'bucket' => $bucketName
            ]);
            
        } catch (Exception $e) {
            Log::error('Firebase Storage initialization failed', [
                'bucket' => $bucketName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        $this->initialized = true;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }

    public function getStorage(): Storage
    {
        return $this->storage;
    }

    public function getBucket(): Bucket
    {
        return $this->bucket;
    }

    /**
     * Upload course video with validation
     */
    public function uploadCourseVideo($file, string $courseId, array $options = []): array
    {
        // Increase execution time for large uploads
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');

        try {
            // Check if Firebase is initialized
            if (!$this->initialized) {
                throw new Exception('Firebase Storage not initialized: ' . $this->error);
            }

            // Validate file
            $maxSize = $options['maxSize'] ?? 500 * 1024 * 1024; // 500MB default
            $allowedMimes = $options['allowedMimes'] ?? [
                'video/mp4', 
                'video/quicktime', 
                'video/x-msvideo', 
                'video/webm',
                'video/x-matroska'
            ];

            if ($file->getSize() > $maxSize) {
                return [
                    'success' => false,
                    'error' => 'File size exceeds maximum allowed size of ' . ($maxSize / 1024 / 1024) . 'MB'
                ];
            }

            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return [
                    'success' => false,
                    'error' => 'Invalid file type. Only video files are allowed. Got: ' . $file->getMimeType()
                ];
            }

            Log::info('Starting video upload', [
                'course_id' => $courseId,
                'filename' => $file->getClientOriginalName(),
                'size_mb' => round($file->getSize() / 1024 / 1024, 2),
                'mime' => $file->getMimeType()
            ]);

            $bucket = $this->getBucket();
            $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $filePath = "courses/{$courseId}/videos/{$fileName}";
            
            // Check file size and use appropriate upload method
            $fileSize = $file->getSize();
            $chunkSize = 5 * 1024 * 1024; // 5MB chunks

            if ($fileSize > $chunkSize) {
                Log::info('Using resumable upload for large file');
                $url = $this->uploadLargeFile($bucket, $file, $filePath, $courseId);
            } else {
                Log::info('Using simple upload for small file');
                $url = $this->uploadSmallFile($bucket, $file, $filePath, $courseId);
            }

            Log::info('Video uploaded successfully', [
                'path' => $filePath,
                'url' => $url
            ]);

            return [
                'success' => true,
                'url' => $url,
                'path' => $filePath,
                'filename' => $fileName,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];

        } catch (Exception $e) {
            Log::error('Video upload failed', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload small files (< 5MB) using simple upload
     */
    private function uploadSmallFile($bucket, $file, $filePath, $courseId)
    {
        try {
            $object = $bucket->upload(
                fopen($file->getRealPath(), 'r'),
                [
                    'name' => $filePath,
                    'metadata' => [
                        'contentType' => $file->getMimeType(),
                        'metadata' => [
                            'courseId' => (string)$courseId,
                            'originalName' => $file->getClientOriginalName(),
                            'uploadedAt' => now()->toIso8601String(),
                            'fileSize' => (string)$file->getSize(),
                        ]
                    ]
                ]
            );

            Log::info('File uploaded to Firebase', ['path' => $filePath]);

            // Make file publicly accessible
            $object->update([
                'acl' => [],
            ], [
                'predefinedAcl' => 'publicRead'
            ]);

            // Get public URL
            $url = sprintf(
                'https://firebasestorage.googleapis.com/v0/b/%s/o/%s?alt=media',
                $bucket->name(),
                urlencode($filePath)
            );

            return $url;

        } catch (Exception $e) {
            Log::error('Simple upload failed', [
                'error' => $e->getMessage(),
                'path' => $filePath
            ]);
            throw $e;
        }
    }

    /**
     * Upload large files (> 5MB) using resumable upload
     */
    private function uploadLargeFile($bucket, $file, $filePath, $courseId)
    {
        try {
            $uploader = $bucket->getResumableUploader(
                fopen($file->getRealPath(), 'r'),
                [
                    'name' => $filePath,
                    'metadata' => [
                        'contentType' => $file->getMimeType(),
                        'metadata' => [
                            'courseId' => (string)$courseId,
                            'originalName' => $file->getClientOriginalName(),
                            'uploadedAt' => now()->toIso8601String(),
                            'fileSize' => (string)$file->getSize(),
                        ]
                    ],
                    'chunkSize' => 5 * 1024 * 1024, // 5MB chunks
                ]
            );

            Log::info('Starting resumable upload', ['path' => $filePath]);

            $object = $uploader->upload();
            
            Log::info('Resumable upload completed', ['path' => $filePath]);

            // Make file publicly accessible
            $object->update([
                'acl' => [],
            ], [
                'predefinedAcl' => 'publicRead'
            ]);

            // Get public URL
            $url = sprintf(
                'https://firebasestorage.googleapis.com/v0/b/%s/o/%s?alt=media',
                $bucket->name(),
                urlencode($filePath)
            );

            return $url;

        } catch (Exception $e) {
            Log::error('Resumable upload failed', [
                'error' => $e->getMessage(),
                'path' => $filePath
            ]);
            throw $e;
        }
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(string $filePath): array
    {
        try {
            $bucket = $this->getBucket();
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

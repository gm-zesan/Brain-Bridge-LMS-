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
            ->withServiceAccount(storage_path('app/firebase/brain-bridge-firebase-adminsdk.json'))
            //Change This to firebase realtime database path
            ->withDatabaseUri('https://brain-bridge-649e2-default-rtdb.firebaseio.com');

        $this->database = $factory->createDatabase();
        $this->messaging = $factory->createMessaging();
        $this->auth = $factory->createAuth();
        $this->storage = $factory->createStorage();
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getMessaging(): Messaging
    {
        return $this->messaging;
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }

    public function getStorage(): Storage
    {
        return $this->storage;
    }

    // Video upload helper method
    public function uploadVideo($file, string $path = 'videos'): array
    {
        try {
            $bucket = $this->storage->getBucket();
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $path . '/' . $fileName;

            // Upload file
            $bucket->upload(
                fopen($file->getRealPath(), 'r'),
                [
                    'name' => $filePath,
                    'metadata' => [
                        'contentType' => $file->getMimeType(),
                    ]
                ]
            );

            // Get download URL
            $object = $bucket->object($filePath);
            $expiresAt = new \DateTime('2099-01-01');
            $downloadUrl = $object->signedUrl($expiresAt);

            return [
                'success' => true,
                'url' => $downloadUrl,
                'path' => $filePath,
                'filename' => $fileName
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Video delete helper method
    public function deleteVideo(string $filePath): bool
    {
        try {
            $bucket = $this->storage->getBucket();
            $object = $bucket->object($filePath);
            $object->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

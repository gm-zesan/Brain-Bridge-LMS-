<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Auth;

class FirebaseService
{
    protected Database $database;
    protected Messaging $messaging;
    protected Auth $auth;

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
}

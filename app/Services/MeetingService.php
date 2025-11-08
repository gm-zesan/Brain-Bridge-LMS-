<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Exception;
use Illuminate\Support\Facades\Log;

class MeetingService
{
    public function createGoogleMeet($teacher, $start, $end, $topic = 'Lesson Session')
    {
        try {
            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken($teacher->google_access_token);

            // Refresh token if expired
            if ($client->isAccessTokenExpired() && $teacher->google_refresh_token) {
                $client->fetchAccessTokenWithRefreshToken($teacher->google_refresh_token);
                $newToken = $client->getAccessToken();

                $teacher->update([
                    'google_access_token' => $newToken['access_token'],
                    'google_token_expires_at' => now()->addSeconds($newToken['expires_in']),
                ]);
            }


            $service = new GoogleCalendar($client);

            $event = new GoogleCalendar\Event([
                'summary' => $topic,
                'start' => ['dateTime' => $start->toRfc3339String(), 'timeZone' => 'Asia/Dhaka'],
                'end' => ['dateTime' => $end->toRfc3339String(), 'timeZone' => 'Asia/Dhaka'],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => uniqid(),
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ]
                ]
            ]);

            $event = $service->events->insert('primary', $event, ['conferenceDataVersion' => 1]);

            return [
                'platform' => 'google_meet',
                'meeting_id' => $event->id,
                'meeting_link' => $event->hangoutLink,
            ];
        } catch (Exception $e) {
            Log::error('Google Meet error: '.$e->getMessage());
            return null;
        }
    }
}

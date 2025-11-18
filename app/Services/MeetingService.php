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
            if (!$teacher->google_access_token) {
                return null;
            }

            $client = $this->getGoogleClient($teacher);
            
            if (!$client) {
                return null;
            }

            $service = new GoogleCalendar($client);

            $event = new GoogleCalendar\Event([
                'summary' => $topic,
                'description' => "Online lesson session via Google Meet",
                'start' => [
                    'dateTime' => $start->toRfc3339String(),
                    'timeZone' => config('app.timezone', 'Asia/Dhaka')
                ],
                'end' => [
                    'dateTime' => $end->toRfc3339String(),
                    'timeZone' => config('app.timezone', 'Asia/Dhaka')
                ],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => uniqid('meet_', true),
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ]
                ],
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60], // 1 day before
                        ['method' => 'popup', 'minutes' => 30],      // 30 minutes before
                    ],
                ],
            ]);

            $event = $service->events->insert('primary', $event, ['conferenceDataVersion' => 1]);

            return [
                'platform' => 'google_meet',
                'meeting_id' => $event->id,
                'meeting_link' => $event->hangoutLink,
            ];
        } catch (Exception $e) {
            return null;
        }
    }


    /**
     * Get and configure Google Client with token refresh
     */
    private function getGoogleClient($teacher)
    {
        try {
            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken($teacher->google_access_token);

            // Handle token refresh if expired
            if ($client->isAccessTokenExpired()) {
                if (!$teacher->google_refresh_token) {
                    return null;
                }

                $newToken = $client->fetchAccessTokenWithRefreshToken(
                    $teacher->google_refresh_token
                );

                // Check for errors in token refresh
                if (isset($newToken['error'])) {
                    return null;
                }

                // Update teacher's token in database
                $teacher->update([
                    'google_access_token' => $newToken['access_token'],
                    'google_token_expires_at' => now()->addSeconds($newToken['expires_in']),
                ]);

                // Set the new token in the client
                $client->setAccessToken($newToken['access_token']);
            }

            return $client;

        } catch (Exception $e) {
            return null;
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes(['https://www.googleapis.com/auth/calendar']);

        $authUrl = $client->createAuthUrl();

        return redirect($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        if ($request->code) {
            $token = $client->fetchAccessTokenWithAuthCode($request->code);

            $user = Auth::user();
            $user->update([
                'google_access_token' => $token['access_token'],
                'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
                'google_token_expires_at' => now()->addSeconds($token['expires_in']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Google Calendar connected successfully! You can now create Google Meet links.',
                'data' => [
                    'connected' => true,
                    'expires_at' => $user->google_token_expires_at
                ]
            ]);
        }

        return response()->json(['error' => 'Authorization failed'], 400);
    }


    /**
     * Check Google connection status
     */
    public function status()
    {
        $user = Auth::user();
        
        $isConnected = !empty($user->google_access_token);
        $isExpired = $isConnected && $user->google_token_expires_at && $user->google_token_expires_at < now();
        
        return response()->json([
            'success' => true,
            'data' => [
                'connected' => $isConnected,
                'expired' => $isExpired,
                'expires_at' => $user->google_token_expires_at,
                'has_refresh_token' => !empty($user->google_refresh_token),
                'can_create_meetings' => $isConnected && !$isExpired,
            ]
        ]);
    }

    /**
     * Disconnect Google account
     */
    public function disconnect()
    {
        $user = Auth::user();
        
        // Revoke token with Google
        if ($user->google_access_token) {
            $client = new GoogleClient();
            $client->revokeToken($user->google_access_token);
        }
        
        // Clear tokens from database
        $user->update([
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Google Calendar disconnected successfully'
        ]);
    }
}

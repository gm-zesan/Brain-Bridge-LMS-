<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Kreait\Firebase\Auth as FirebaseAuth;

class FirebaseUserSync
{
    protected $auth;

    public function __construct(FirebaseAuth $auth)
    {
        $this->auth = $auth;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uid = $request->firebase_uid ?? null;

        if ($uid) {
            try {
                $firebaseUser = $this->auth->getUser($uid);

                User::where('firebase_uid', $uid)->update([
                    'name' => $firebaseUser->displayName ?? 'Unknown',
                    'email' => $firebaseUser->email,
                ]);

            } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                User::where('firebase_uid', $uid)->delete();
            }
        }
        return $next($request);
    }
}

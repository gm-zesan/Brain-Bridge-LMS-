<?php

use App\Http\Controllers\Api\V1\Auth\FirebaseAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [FirebaseAuthController::class, 'register']);
Route::post('/password/reset', [FirebaseAuthController::class, 'resetPassword']);
Route::post('/google/login', [FirebaseAuthController::class, 'googleLogin']);
Route::post('/login', [FirebaseAuthController::class, 'login']);

Route::middleware(['firebase.auth', 'firebase.sync'])->group(function () {
    Route::get('/me', [FirebaseAuthController::class, 'me']);
    Route::post('/logout', [FirebaseAuthController::class, 'logout']);
    Route::delete('/delete', [FirebaseAuthController::class, 'destroy']);
});
<?php

use App\Http\Controllers\Api\V1\Auth\FirebaseAuthController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\TeacherLevelController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VideoLessonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [FirebaseAuthController::class, 'register']);
Route::post('/password/reset', [FirebaseAuthController::class, 'resetPassword']);
Route::post('/google/login', [FirebaseAuthController::class, 'googleLogin']);
Route::post('/login', [FirebaseAuthController::class, 'login']);
Route::apiResource('teachers', TeacherController::class)->only(['store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [FirebaseAuthController::class, 'me']);
    Route::post('/logout', [FirebaseAuthController::class, 'logout']);
    Route::delete('/delete', [FirebaseAuthController::class, 'destroy']);


    // Teacher Routes
    Route::apiResource('users', UserController::class);
    Route::apiResource('students', StudentController::class);

    Route::apiResource('teacher-levels', TeacherLevelController::class);
    Route::apiResource('teachers', TeacherController::class)->except(['store']);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('video-lessons', VideoLessonController::class);
    
});
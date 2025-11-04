<?php

use App\Http\Controllers\Api\V1\Auth\FirebaseAuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\SkillController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\TeacherLevelController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VideoLessonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::apiResource('teachers', TeacherController::class)->only(['store']);
Route::apiResource('students', StudentController::class)->only(['store']);




Route::post('/register', [FirebaseAuthController::class, 'register']);
Route::post('/login', [FirebaseAuthController::class, 'login']);

Route::post('/password/reset', [FirebaseAuthController::class, 'resetPassword']);
Route::post('/google/login', [FirebaseAuthController::class, 'googleLogin']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [FirebaseAuthController::class, 'me']);
    Route::post('/logout', [FirebaseAuthController::class, 'logout']);
    Route::delete('/delete', [FirebaseAuthController::class, 'destroy']);


    // Teacher Routes
    Route::apiResource('users', UserController::class);
    Route::apiResource('students', StudentController::class)->except(['store']);

    Route::apiResource('teacher-levels', TeacherLevelController::class);
    Route::apiResource('teachers', TeacherController::class)->except(['store']);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('skills', SkillController::class);
    
    Route::apiResource('courses', CourseController::class);
    Route::apiResource('modules', ModuleController::class);
    Route::apiResource('video-lessons', VideoLessonController::class);


    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/stripe/initiate', [TransactionController::class, 'initiateStripePayment']);
        Route::get('/success', [TransactionController::class, 'success']);
        Route::get('/cancel', [TransactionController::class, 'cancel']);
        Route::post('/stripe/webhook', [TransactionController::class, 'handleWebhook']);
        Route::post('/manual', [TransactionController::class, 'manualStore']);
        Route::get('/{transaction}', [TransactionController::class, 'show']);
        Route::delete('/{transaction}', [TransactionController::class, 'destroy']);
    });

    
});
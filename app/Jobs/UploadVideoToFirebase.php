<?php

namespace App\Jobs;

use App\Models\VideoLesson;
use App\Services\FirebaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class UploadVideoToFirebase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout for large files
    public $tries = 3; // Retry 3 times on failure
    public $backoff = 60; // Wait 60 seconds between retries

    protected $videoLessonId;
    protected $tempPath;
    protected $courseId;

    /**
     * Create a new job instance.
     */
    public function __construct($videoLessonId, $tempPath, $courseId)
    {
        $this->videoLessonId = $videoLessonId;
        $this->tempPath = $tempPath;
        $this->courseId = $courseId;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService)
    {
        try {
            $videoLesson = VideoLesson::findOrFail($this->videoLessonId);

            // Update status to processing
            $videoLesson->update(['upload_status' => 'processing']);

            Log::info('Starting background video upload', [
                'video_lesson_id' => $this->videoLessonId,
                'temp_path' => $this->tempPath
            ]);

            // Get the temporary file
            $tempFilePath = Storage::disk('local')->path($this->tempPath);

            if (!file_exists($tempFilePath)) {
                throw new Exception("Temporary file not found: {$tempFilePath}");
            }

            // Create a fake UploadedFile object for FirebaseService
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempFilePath,
                $videoLesson->filename,
                $videoLesson->mime_type,
                null,
                true // Mark as test file to skip validation
            );

            // Upload to Firebase
            $uploadResult = $firebaseService->uploadCourseVideo(
                $uploadedFile,
                $this->courseId,
                [
                    'maxSize' => 500 * 1024 * 1024, // 500MB
                    'allowedMimes' => ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm', 'video/avi']
                ]
            );

            if (!$uploadResult['success']) {
                throw new Exception('Upload failed: ' . $uploadResult['error']);
            }

            // Update video lesson with Firebase data
            $videoLesson->update([
                'video_path' => $uploadResult['path'],
                'video_url' => $uploadResult['url'],
                'filename' => $uploadResult['filename'],
                'file_size' => $uploadResult['size'],
                'mime_type' => $uploadResult['mime_type'],
                'upload_status' => 'completed',
                'uploaded_at' => now(),
                'temp_path' => null, // Clear temp path
            ]);

            // Delete temporary file
            Storage::disk('local')->delete($this->tempPath);

            Log::info('Video uploaded successfully', [
                'video_lesson_id' => $this->videoLessonId,
                'firebase_path' => $uploadResult['path'],
                'url' => $uploadResult['url']
            ]);

            // Check if all videos for this course are uploaded
            $this->checkAndPublishCourse();

        } catch (Exception $e) {
            Log::error('Video upload job failed', [
                'video_lesson_id' => $this->videoLessonId,
                'temp_path' => $this->tempPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update status to failed
            VideoLesson::where('id', $this->videoLessonId)->update([
                'upload_status' => 'failed',
                'upload_error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Check if all videos are uploaded and publish course
     */
    private function checkAndPublishCourse()
    {
        try {
            $videoLesson = VideoLesson::findOrFail($this->videoLessonId);
            $course = $videoLesson->module->course;

            // Count total and completed videos
            $totalVideos = VideoLesson::whereHas('module', function($query) use ($course) {
                $query->where('course_id', $course->id);
            })->count();

            $completedVideos = VideoLesson::whereHas('module', function($query) use ($course) {
                $query->where('course_id', $course->id);
            })->where('upload_status', 'completed')->count();

            Log::info('Checking course completion', [
                'course_id' => $course->id,
                'total_videos' => $totalVideos,
                'completed_videos' => $completedVideos
            ]);

            // If all videos are uploaded, mark course as ready to publish
            if ($totalVideos === $completedVideos && $totalVideos > 0) {
                $course->update([
                    'is_published' => true,
                    'published_at' => now(),
                ]);

                // Also publish all videos
                VideoLesson::whereHas('module', function($query) use ($course) {
                    $query->where('course_id', $course->id);
                })->update(['is_published' => true]);

                Log::info('Course published automatically', [
                    'course_id' => $course->id
                ]);
            }

        } catch (Exception $e) {
            Log::error('Failed to check course completion', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception)
    {
        Log::error('Video upload job permanently failed', [
            'video_lesson_id' => $this->videoLessonId,
            'temp_path' => $this->tempPath,
            'error' => $exception->getMessage()
        ]);

        // Update video status
        VideoLesson::where('id', $this->videoLessonId)->update([
            'upload_status' => 'failed',
            'upload_error' => 'Upload failed after 3 attempts: ' . $exception->getMessage(),
        ]);
    }
}

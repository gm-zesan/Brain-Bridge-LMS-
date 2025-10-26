<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\VideoLesson;
use Illuminate\Http\Request;

class VideoLessonController extends Controller
{
    public function index()
    {
        return response()->json(VideoLesson::with(['teacher', 'subject'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'old_price' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|integer|min:0',
            'video_url' => 'required|string',
            'is_published' => 'boolean',
        ]);

        $lesson = VideoLesson::create($data);
        return response()->json($lesson, 201);
    }

    public function show(VideoLesson $videoLesson)
    {
        return response()->json($videoLesson->load(['teacher', 'subject']));
    }

    public function update(Request $request, VideoLesson $videoLesson)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'old_price' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|integer|min:0',
            'video_url' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $videoLesson->update($data);
        return response()->json($videoLesson);
    }

    public function destroy(VideoLesson $videoLesson)
    {
        $videoLesson->delete();
        return response()->json(['message' => 'Video lesson deleted successfully']);
    }
}

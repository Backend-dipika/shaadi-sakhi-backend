<?php

namespace App\Http\Controllers;

use App\Models\VideoTestimonial;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Str;
use Illuminate\Support\Facades\Storage;

class TestimonialVideoController extends Controller
{
    /**
     * Get all video testimonials
     *
     * @group Video Testimonials
     *
     * @response 200 {
     *  "data": [
     *    {
     *      "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *      "video_path": "video_testimonials/video.mp4"
     *    }
     *  ]
     * }
     */
    public function index()
    {
        try {
            $data = VideoTestimonial::latest()->get();

            return response()->json([
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching video testimonials', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * Store a new video testimonial
     *
     * @group Video Testimonials
     *
     * @bodyParam video file required Video file (mp4, max 50MB)
     *
     * @response 201 {
     *  "message": "Video testimonial created",
     *  "data": {
     *      "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *      "video_path": "video_testimonials/video.mp4"
     *  }
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video' => 'required|file|mimes:mp4,mov|max:61200', // max 60MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $filePath = $request->file('video')->store('video_testimonials', 'public');

            // Add storage prefix (if you want full URL path in DB)
            $videoPath = 'storage/' . $filePath;

            $video = VideoTestimonial::create([
                'uuid' => Str::uuid(),
                'video_path' => $videoPath,
            ]);

            return response()->json([
                'message' => 'Video testimonial created',
                'data' => $video
            ], 201);
        } catch (Exception $e) {
            Log::error('Error creating video testimonial', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * Show a single video testimonial
     *
     * @group Video Testimonials
     * @urlParam uuid string required The UUID of the video testimonial
     *
     * @response 200 {
     *  "data": {
     *      "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *      "video_path": "video_testimonials/video.mp4"
     *  }
     * }
     */
    public function show($uuid)
    {
        try {
            $video = VideoTestimonial::where('uuid', $uuid)->first();

            if (!$video) {
                return response()->json(['message' => 'Not found'], 404);
            }

            return response()->json(['data' => $video], 200);
        } catch (Exception $e) {
            Log::error('Error fetching video testimonial', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * Update video testimonial
     *
     * @group Video Testimonials
     * @urlParam uuid string required The UUID of the video testimonial
     * @bodyParam video file New video file
     *
     * @response 200 {
     *  "message": "Video updated successfully"
     * }
     */
    public function update(Request $request, $uuid)
    {
        $video = VideoTestimonial::where('uuid', $uuid)->first();

        if (!$video) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'video' => 'required|file|mimes:mp4,mov|max:61200', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->hasFile('video')) {
                // Normalize old path (remove "storage/" if present)
                $oldPath = str_replace('storage/', '', $video->video_path);

                // Delete old file safely
                if ($video->video_path && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                // Upload new file
                $filePath = $request->file('video')->store('video_testimonials', 'public');

                // Save new path (recommended: WITHOUT storage/)
                $video->update([
                    'video_path' => 'storage/' . $filePath
                ]);
            }

            return response()->json([
                'message' => 'Video updated successfully',
                'data' => $video
            ], 200);
        } catch (Exception $e) {
            Log::error('Error updating video testimonial', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * Delete video testimonial
     *
     * @group Video Testimonials
     * @urlParam uuid string required The UUID of the video testimonial
     *
     * @response 200 {
     *  "message": "Deleted successfully"
     * }
     */
    public function destroy($uuid)
    {
        try {
            $video = VideoTestimonial::where('uuid', $uuid)->first();

            if (!$video) {
                return response()->json(['message' => 'Not found'], 404);
            }

            $path = str_replace('storage/', '', $video->video_path);

            if ($video->video_path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            $video->delete();

            return response()->json([
                'message' => 'Deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error('Error deleting video testimonial', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}

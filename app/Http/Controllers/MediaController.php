<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\MediaMetaData;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Str;

class MediaController extends Controller
{

    /**
     * Get all media with metadata and children.
     *
     * @group Media
     *
     * @response 200 {
     *   "data": [
     *       {
     *           "id": 1,
     *           "title": "Summer Gallery",
     *           "description": "Gallery description",
     *           "cover_photo": "media/1/cover/photo.jpg",
     *           "meta": [
     *               {
     *                   "id": 1,
     *                   "type": "image",
     *                   "path": "media/1/image1.jpg"
     *               }
     *           ],
     *           "children": []
     *       }
     *   ]
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */

    public function index()
    {
        try {

            $media = Media::with('meta', 'children')->get();
            return response()->json(['data' => $media], 200);
        } catch (Exception $e) {
            Log::warning('Error in gallery list', [
                'message' =>  $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Get a single media by UUID with metadata and children.
     *
     * @group Media
     * @urlParam uuid string required The UUID of the media. Example: "123e4567-e89b-12d3-a456-426614174000"
     *
     * @response 200 {
     *   "data": {
     *       "id": 1,
     *       "title": "Summer Gallery",
     *       "description": "Gallery description",
     *       "cover_photo": "media/1/cover/photo.jpg",
     *       "meta": [
     *           {
     *               "id": 1,
     *               "type": "image",
     *               "path": "media/1/image1.jpg"
     *           }
     *       ],
     *       "children": []
     *   }
     * }
     * @response 404 {
     *   "message": "Media not found"
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */

    public function show($uuid)
    {
        try {

            $media = Media::with('meta', 'children')->where('uuid', $uuid)->first();
            if (!$media) {
                return response()->json(['message' => 'Media not found'], 404);
            }
            return response()->json(['data' => $media], 200);
        } catch (Exception $e) {
            Log::warning('Error in gallery show', [
                'message' =>  $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Create a new media entry with cover photo and additional files.
     *
     * @group Media
     * @bodyParam title string required Media title. Example: "Summer Gallery"
     * @bodyParam description string required Media Location. Example: "Thane"
     * @bodyParam cover_photo file required Cover photo file (jpg, jpeg, png, max 10MB)
     * @bodyParam files file[] required Array of media files (jpg, jpeg, png, mp4, max 20MB each)
     *
     * @response 201 {
     *   "message": "Media created successfully",
     *   "data": {
     *       "id": 1,
     *       "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *       "title": "Summer Gallery",
     *       "description": "Gallery of summer images",
     *       "cover_photo": "media/1/cover/photo.jpg",
     *       "meta": [
     *           {
     *               "id": 1,
     *               "type": "image",
     *               "path": "media/1/image1.jpg"
     *           }
     *       ]
     *   }
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'cover_photo' => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'files' => 'required|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,mp4|max:20480'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $media = Media::create([
                'uuid' => Str::uuid(),
                'title' => $request->title,
                'description' => $request->description,
            ]);

            if ($request->hasFile('cover_photo')) {

                // Upload new cover
                $filePath = $request->file('cover_photo')->store(
                    'media/' . $media->id . '/cover',
                    'public'
                );

                // Store WITH storage/ prefix
                $media->update([
                    'cover_photo' => 'storage/' . $filePath
                ]);
            }

            // Store additional files in meta
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {

                    $type = str_contains($file->getMimeType(), 'video') ? 'video' : 'image';

                    $filePath = $file->store('media/' . $media->id, 'public');

                    MediaMetaData::create([
                        'media_id' => $media->id,
                        'type' => $type,
                        'path' => 'storage/' . $filePath // ✅ add prefix
                    ]);
                }
            }

            return response()->json(['message' => 'Media created successfully', 'data' => $media->load('meta')], 201);
        } catch (Exception $e) {
            Log::warning('Error creating media', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * Update existing media and manage files.
     *
     * @group Media
     * @urlParam uuid string required The UUID of the media. Example: "123e4567-e89b-12d3-a456-426614174000"
     * @bodyParam title string optional Media title
     * @bodyParam description string optional Media description
     * @bodyParam cover_photo file optional New cover photo (jpg, jpeg, png, max 10MB)
     * @bodyParam files file[] optional Array of new media files (jpg, jpeg, png, mp4, max 20MB each)
     * @bodyParam delete_files integer[] optional Array of meta file IDs to delete
     *
     * @response 200 {
     *   "message": "Media updated successfully",
     *   "data": {
     *       "id": 1,
     *       "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *       "title": "Updated Gallery",
     *       "description": "Updated description",
     *       "cover_photo": "media/1/cover/photo.jpg",
     *       "meta": [
     *           {
     *               "id": 2,
     *               "type": "video",
     *               "path": "media/1/video1.mp4"
     *           }
     *       ]
     *   }
     * }
     * @response 404 {
     *   "message": "Media not found"
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function update(Request $request, $uuid)
    {
        $media = Media::where('uuid', $uuid)->first();
        if (!$media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'cover_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,mp4|max:20480',
            'delete_files' => 'nullable|array',
            'delete_files.*' => 'integer|exists:media_meta_data,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $media->update($request->only(['title', 'description']));

            if ($request->hasFile('cover_photo')) {

                // Normalize old path (remove "storage/")
                $oldPath = str_replace('storage/', '', $media->cover_photo);

                // Delete old cover
                if ($media->cover_photo && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                // Upload new cover
                $filePath = $request->file('cover_photo')->store(
                    'media/' . $media->id . '/cover',
                    'public'
                );

                // Store WITH storage/ prefix
                $media->update([
                    'cover_photo' => 'storage/' . $filePath
                ]);
            }

            // Delete existing meta files
            if ($request->has('delete_files')) {
                $filesToDelete = MediaMetaData::whereIn('id', $request->delete_files)
                    ->where('media_id', $media->id)
                    ->get();

                foreach ($filesToDelete as $file) {

                    // Normalize path
                    $oldPath = str_replace('storage/', '', $file->path);

                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }

                    $file->delete();
                }
            }

            // Add new files
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {

                    $type = str_contains($file->getMimeType(), 'video') ? 'video' : 'image';

                    $filePath = $file->store('media/' . $media->id, 'public');

                    MediaMetaData::create([
                        'media_id' => $media->id,
                        'type' => $type,
                        'path' => 'storage/' . $filePath // ✅ add prefix
                    ]);
                }
            }

            return response()->json(['message' => 'Media updated successfully', 'data' => $media->load('meta')], 200);
        } catch (Exception $e) {
            Log::warning('Error updating media', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * Delete media along with cover photo and all meta files.
     *
     * @group Media
     * @urlParam uuid string required The UUID of the media. Example: "123e4567-e89b-12d3-a456-426614174000"
     *
     * @response 200 {
     *   "message": "Media deleted successfully"
     * }
     * @response 404 {
     *   "message": "Media not found"
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function destroy($uuid)
    {
        $media = Media::where('uuid', $uuid)->first();
        if (!$media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        try {
            Storage::disk('public')->deleteDirectory('media/' . $media->id);

            $media->meta()->delete();

            // Delete media (cascades to children and meta due to foreign keys)
            $media->delete();

            return response()->json(['message' => 'Media deleted successfully'], 200);
        } catch (Exception $e) {
            Log::warning('Error deleting media', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}

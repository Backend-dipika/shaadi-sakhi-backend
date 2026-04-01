<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Str;

class TestimonialController extends Controller
{
    /**
     * Get all testimonials.
     *
     * @group Testimonials
     *
     * @response 200 {
     *   "data": [
     *       {
     *           "id": 1,
     *           "name": "John Doe",
     *           "description": "Great service!",
     *           "rating": 5,
     *           "review": 5,
     *           "profile_photo": "testimonials/photo.jpg"
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
            $testimonial = Testimonial::get();
            return response()->json(['data' => $testimonial], 200);
        } catch (Exception $e) {
            Log::warning('Error in testimonials list', [
                'message' =>  $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Get a single testimonial by UUID.
     *
     * @group Testimonials
     * @urlParam uuid string required The UUID of the testimonial. Example: "123e4567-e89b-12d3-a456-426614174000"
     *
     * @response 200 {
     *   "data": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "description": "Great service!",
     *       "rating": 5,
     *       "review": 5,
     *       "profile_photo": "testimonials/photo.jpg"
     *   }
     * }
     * @response 404 {
     *   "message": "Testimonial not found"
     * }
     */
    public function show($uuid)
    {
        $testimonial = Testimonial::where('uuid', $uuid)->first();

        if (!$testimonial) {
            return response()->json(['message' => 'Testimonial not found'], 404);
        }

        return response()->json(['data' => $testimonial], 200);
    }

    /**
     * Create a new testimonial.
     *
     * @group Testimonials
     * @bodyParam name string required Name of the person. Example: "John Doe"
     * @bodyParam description string optional Description. Example: "Excellent experience"
     * @bodyParam profile_photo file optional Profile photo (jpg, jpeg, png, max 10MB)
     * @bodyParam rating integer required Rating 1-5. Example: 5
     * @bodyParam review integer optional Review score 0-5. Example: 5
     *
     * @response 201 {
     *   "message": "Testimonial added successfully",
     *   "data": {
     *       "id": 1,
     *       "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *       "name": "John Doe",
     *       "description": "Excellent experience",
     *       "rating": 5,
     *       "review": 5,
     *       "profile_photo": "testimonials/photo.jpg"
     *   }
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|min:0|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $profilePhotoPath = null;
            if ($request->hasFile('profile_photo')) {
                $filePath = $request->file('profile_photo')->store('testimonials', 'public');

                // Add storage prefix for public URL
                $profilePhotoPath = 'storage/' . $filePath;
            }
            $testimonial = Testimonial::create([
                'uuid' => Str::uuid(),
                'name' => $request->name,
                'description' => $request->description,
                'rating' => $request->rating,
                'review' => $request->review,
                'profile_photo' => $profilePhotoPath,
            ]);


            return response()->json([
                'message' => 'Testimonial added successfully',
                'data' => $testimonial
            ], 201);
        } catch (Exception $e) {
            Log::warning('Error adding testimonial', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * Update an existing testimonial.
     *
     * @group Testimonials
     * @urlParam uuid string required The UUID of the testimonial. Example: "123e4567-e89b-12d3-a456-426614174000"
     * @bodyParam name string optional Name of the person
     * @bodyParam description string optional Description
     * @bodyParam profile_photo file optional New profile photo (jpg, jpeg, png, max 10MB)
     * @bodyParam rating integer optional Rating 1-5
     * @bodyParam review integer optional Review score 0-5
     *
     * @response 200 {
     *   "message": "Testimonial updated successfully",
     *   "data": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "description": "Updated description",
     *       "rating": 5,
     *       "review": 5,
     *       "profile_photo": "testimonials/photo_updated.jpg"
     *   }
     * }
     * @response 404 {
     *   "message": "Testimonial not found"
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function update(Request $request, $uuid)
    {
        $testimonial = Testimonial::where('uuid', $uuid)->first();
        if (!$testimonial) {
            return response()->json(['message' => 'Testimonial not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'review' => 'nullable|string|min:0|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update fields
            $testimonial->update($request->only(['name', 'description', 'rating', 'review']));

            // Replace profile photo if provided
            if ($request->hasFile('profile_photo')) {
                // Remove "storage/" if you're saving full path in DB
                $oldPath = str_replace('storage/', '', $testimonial->profile_photo);

                // Delete old file (if exists)
                if ($testimonial->profile_photo && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }

                // Upload new file
                $filePath = $request->file('profile_photo')->store('testimonials', 'public');

                // Save with storage prefix
                $testimonial->update([
                    'profile_photo' => 'storage/' . $filePath
                ]);
            }

            return response()->json([
                'message' => 'Testimonial updated successfully',
                'data' => $testimonial
            ], 200);
        } catch (Exception $e) {
            Log::warning('Error updating testimonial', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * Delete a testimonial along with profile photo.
     *
     * @group Testimonials
     * @urlParam uuid string required The UUID of the testimonial. Example: "123e4567-e89b-12d3-a456-426614174000"
     *
     * @response 200 {
     *   "message": "Testimonial deleted successfully"
     * }
     * @response 404 {
     *   "message": "Testimonial not found"
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */

    public function destroy($uuid)
    {
        $testimonial = Testimonial::where('uuid', $uuid)->first();

        if (!$testimonial) {
            return response()->json(['message' => 'Testimonial not found'], 404);
        }

        try {
            // Delete profile photo if exists
            $oldPath = str_replace('storage/', '', $testimonial->profile_photo);

            // Delete old file (if exists)
            if ($testimonial->profile_photo && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $testimonial->delete();

            return response()->json(['message' => 'Testimonial deleted successfully'], 200);
        } catch (Exception $e) {
            Log::warning('Error deleting testimonial', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}

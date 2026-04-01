<?php

namespace App\Http\Controllers;

use App\Models\EventMetaData;
use App\Models\Events;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Str;

class EventsController extends Controller
{

    /**
     * Get paginated list of events with metadata.
     *
     * @group Events
     * @queryParam page integer Optional. Page number for pagination. Example: 1
     *
     * @response 200 {
     *   "message": "Events fetched successfully",
     *   "data": [
     *       {
     *           "id": 1,
     *           "title": "Art Expo",
     *           "description": "An art exhibition",
     *           "venue": "Gallery 1",
     *           "starts_at": "2026-04-01T09:00:00Z",
     *           "ends_at": "2026-04-05T17:00:00Z",
     *       }
     *   ]
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function index(Request $request)
    {
        try {
            $events = Events::with('metadata')->latest()->paginate(10);

            return response()->json([
                'message' => 'Events fetched successfully',
                'data' => $events
            ], 200);
        } catch (Exception $e) {
            Log::warning('Error in Events', [
                'message' =>  $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Get a single event by UUID with metadata.
     *
     * @group Events
     * @urlParam uuid string required The UUID of the event. Example: "123e4567-e89b-12d3-a456-426614174000"
     *
     * @response 200 {
     *   "data": {
     *       "id": 1,
     *       "title": "Art Expo",
     *       "description": "An art exhibition",
     *       "venue": "Gallery 1",
     *       "starts_at": "2026-04-01T09:00:00Z",
     *       "ends_at": "2026-04-05T17:00:00Z",
     *       "metadata": [
     *           {
     *               "id": 1,
     *               "type": "image",
     *               "path": "events/1/image.jpg"
     *           }
     *       ]
     *   }
     * }
     * @response 404 {
     *   "message": "Event not found"
     * }
     */
    public function show($uuid)
    {
        $events = Events::with('metadata')->where('uuid', $uuid)->first();

        if (!$events) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        return response()->json(['data' => $events], 200);
    }

    /**
     * Get paginated list of upcoming events.
     *
     * @group Events
     * @queryParam page integer Optional. Page number for pagination. Example: 1
     *
     * @response 200 {
     *   "message": "Upcoming events fetched successfully",
     *   "data": [
     *       {
     *           "id": 1,
     *           "title": "Art Expo",
     *           "venue": "Gallery 1",
     *           "starts_at": "2026-04-01T09:00:00Z",
     *           "ends_at": "2026-04-05T17:00:00Z"
     *       }
     *   ]
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function getUpcomingEvents(Request $request)
    {
        try {
            $events = Events::with('metadata')
                ->where('starts_at', '>=', Carbon::now())
                ->orderBy('starts_at', 'asc')
                ->paginate(10);

            return response()->json([
                'message' => 'Upcoming events fetched successfully',
                'data' => $events
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Create a new event with optional media files.
     *
     * @group Events
     * @bodyParam title string required Event title. Example: "Art Expo"
     * @bodyParam description string required Event description. Example: "An exhibition of modern art"
     * @bodyParam venue string required Event venue. Example: "Gallery 1"
     * @bodyParam starts_at date required Event start date. Example: "2026-04-01T09:00:00Z"
     * @bodyParam ends_at date required Event end date. Example: "2026-04-05T17:00:00Z"
     * @bodyParam file file[] Optional Array of image/video files.
     *
     * @response 201 {
     *   "message": "Event created successfully",
     *   "data": {
     *       "id": 1,
     *       "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *       "title": "Art Expo"
     *   }
     * }
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *       "title": ["The title field is required."]
     *   }
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function store(Request $request)
    {
        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'venue' => 'required|string|max:150',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'file' => 'nullable|array',
            'file.*' => 'file|mimes:jpg,jpeg,png,mp4|max:20480'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            //  Create Event
            $event = Events::create([
                'uuid' => Str::uuid(),
                'title' => $request->title,
                'description' => $request->description,
                'venue' => $request->venue,
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
            ]);

            //Store Event Meta Data (if file exists)
            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $file) {

                    $type = str_contains($file->getMimeType(), 'video') ? 'video' : 'image';
                    $path = $file->store('events/' . $event->id, 'public');

                    EventMetaData::create([
                        'event_id' => $event->id,
                        'type' => $type,
                        'path' => 'storage/' . $path,
                    ]);
                }
            }
            return response()->json([
                'message' => 'Event created successfully',
                'data' => $event
            ], 201);
        } catch (Exception $e) {
            Log::warning('Error in Events', [
                'message' =>  $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Update an existing event and manage media files.
     *
     * @group Events
     * @urlParam uuid string required The UUID of the event. Example: "123e4567-e89b-12d3-a456-426614174000"
     * @bodyParam title string Optional Event title.
     * @bodyParam description string Optional Event description.
     * @bodyParam venue string Optional Event venue.
     * @bodyParam starts_at date Optional Event start date.
     * @bodyParam ends_at date Optional Event end date.
     * @bodyParam file file[] Optional Array of new image/video files.
     * @bodyParam delete_files integer[] Optional Array of metadata IDs to delete.
     *
     * @response 200 {
     *   "message": "Event updated successfully",
     *   "data": {
     *       "id": 1,
     *       "uuid": "123e4567-e89b-12d3-a456-426614174000",
     *       "title": "Updated Expo"
     *   }
     * }
     * @response 404 {
     *   "message": "Event not found."
     * }
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *       "title": ["The title must be a string."]
     *   }
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function update(Request $request, $uuid)
    {
        // Find Event
        $event = Events::where('uuid', $uuid)->first();
        if (!$event) {
            return response()->json([
                'message' => 'Event not found.'
            ], 404);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'venue' => 'sometimes|required|string|max:150',
            'starts_at' => 'sometimes|required|date',
            'ends_at' => 'sometimes|required|date|after_or_equal:starts_at',
            'file' => 'nullable|array',
            'file.*' => 'file|mimes:jpg,jpeg,png,mp4|max:20480',
            'delete_files' => 'nullable|array',
            'delete_files.*' => 'integer|exists:event_meta_data,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update Event details
            $event->update($request->only([
                'title',
                'description',
                'venue',
                'starts_at',
                'ends_at'
            ]));

            // Delete existing files if requested
            if ($request->has('delete_files')) {

                $filesToDelete = EventMetaData::whereIn('id', $request->delete_files)
                    ->where('event_id', $event->id)
                    ->get();

                foreach ($filesToDelete as $fileMeta) {

                    // Normalize path (remove "storage/")
                    $oldPath = str_replace('storage/', '', $fileMeta->path);

                    // Delete file
                    if ($fileMeta->path && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }

                    // Delete DB record
                    $fileMeta->delete();
                }
            }

            // Handle new files
            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $file) {
                    $type = str_contains($file->getMimeType(), 'video') ? 'video' : 'image';
                    $path = $file->store('events/' . $event->id, 'public');

                    EventMetaData::create([
                        'event_id' => $event->id,
                        'type' => $type,
                        'path' => 'storage/' . $path,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Event updated successfully',
                'data' => $event
            ], 200);
        } catch (Exception $e) {
            Log::warning('Error updating Event', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    /**
     * Delete an event along with associated media files.
     *
     * @group Events
     * @urlParam uuid string required The UUID of the event. Example: "123e4567-e89b-12d3-a456-426614174000"
     *
     * @response 200 {
     *   "message": "Event deleted successfully"
     * }
     * @response 404 {
     *   "message": "Event not found."
     * }
     * @response 500 {
     *   "message": "Something went wrong"
     * }
     */
    public function destroy($uuid)
    {
        $event = Events::where('uuid', $uuid)->first();
        if (!$event) {
            return response()->json([
                'message' => 'Event not found.'
            ], 404);
        }

        try {
            // Delete associated files from storage
            foreach ($event->metadata as $meta) {
                $oldPath = str_replace('storage/', '', $meta->path);

                if ($meta->path && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Delete metadata entries
            $event->metadata()->delete();

            // Delete event
            $event->delete();

            return response()->json([
                'message' => 'Event deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Log::warning('Error deleting Event', ['message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }
}

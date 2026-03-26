<?php

namespace App\Http\Controllers;

use App\Mail\CollaborationFormMail;
use App\Models\Category;
use App\Models\ExhibitionEnquiry;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CollaborationController extends Controller
{
    /**
     * Get Categories
     * 
     * Fetch all available categories.
     * 
     * @group Collaboration
     * 
     * @response 200 {
     *  "message": "Categories fetched successfully",
     *  "data": [
     *    {
     *      "id": 1,
     *      "name": "Fashion"
     *    }
     *  ]
     * }
     */
    public function categories(Request $request)
    {
        try {
            $categories = Category::all();

            return response()->json([
                'message' => 'Categories fetched successfully',
                'data' => $categories
            ], 200);
        } catch (Exception $e) {
            Log::error('Categories Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    /**
     * Store Exhibition Enquiry
     * 
     * Submit a collaboration/exhibition enquiry.
     * 
     * @group Collaboration
     * 
     * @bodyParam name string required User full name. Example: John Doe
     * @bodyParam brand_name string required Brand name. Example: Nike
     * @bodyParam email string required User email. Example: john@example.com
     * @bodyParam contact_number string required Phone number. Example: 9876543210
     * @bodyParam category_id integer required Category ID. Example: 1
     * @bodyParam social_media string required Social media link. Example: https://instagram.com/johndoe
     * 
     * @response 201 {
     *  "message": "Enquiry submitted successfully",
     *  "data": {
     *    "uuid": "generated-uuid",
     *    "name": "John Doe",
     *    "brand_name": "Nike",
     *    "email": "john@example.com"
     *  }
     * }
     * 
     * @response 422 {
     *  "message": "Validation failed",
     *  "errors": {
     *    "email": ["The email field is required."]
     *  }
     * }
     */
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'brand_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:15',
            'category_id' => 'required|exists:categories,id',
            'social_media' => 'required|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store Data
            $enquiry = ExhibitionEnquiry::create([
                'uuid' => Str::uuid(),
                'name' => $request->name,
                'brand_name' => $request->brand_name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'category_id' => $request->category_id,
                'social_media' => $request->social_media,
            ]);

            // Send Email
            try {
                $categoryName = Category::find($request->category_id)->value('name');
                Log::info('sakhi', ['MAIL_SEND_TO' => config('mail.admin_email')]);
                Mail::to(config('mail.admin_email'))->queue(new CollaborationFormMail($enquiry, $categoryName));
            } catch (Exception $e) {
                Log::error('Contact Mail Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return response()->json([
                'message' => 'Enquiry submitted successfully',
                'data' => $enquiry
            ], 201);
        } catch (Exception $e) {
            Log::error('Exhibition Enquiry Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use App\Models\ContactMessage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContactMessageController extends Controller
{
    /**
     * Submit Contact Message
     * 
     * Allows users to send a contact message without login.
     * 
     * @group Contact
     * 
     * @bodyParam name string required User full name. Example: John Doe
     * @bodyParam email string required User email address. Example: john@example.com
     * @bodyParam contact_number string required Phone number. Example: 9876543210
     * @bodyParam category_id integer required Category ID. Example: 1
     * @bodyParam message string required Message content. Example: I am interested in your services.
     * 
     * @response 201 {
     *  "message": "Message submitted successfully",
     *  "data": {
     *    "uuid": "generated-uuid",
     *    "name": "John Doe",
     *    "email": "john@example.com",
     *    "contact_number": "9876543210",
     *    "category_id": 1,
     *    "message": "I am interested in your services"
     *  }
     * }
     * 
     * @response 422 {
     *  "message": "Validation failed",
     *  "errors": {
     *    "email": ["The email field is required."]
     *  }
     * }
     * 
     * @response 500 {
     *  "message": "Something went wrong"
     * }
     */
    public function store(Request $request)
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'contact_number' => 'required|string|max:15',
                'category_id' => 'required|exists:categories,id',
                'message' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }


            // Store Data
            $contact = ContactMessage::create([
                'uuid' => Str::uuid(),
                'name' => $request->name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'category_id' => $request->category_id,
                'message' => $request->message,
            ]);
            try {
                Log::info('sakhi', [
                    'MAIL_SEND_TO' => config('mail.admin_email')
                ]);
                // Send Email
                Mail::to(config('mail.admin_email'))->queue(new ContactFormMail($contact));
            } catch (Exception $e) {
                Log::error('Contact Mail Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return response()->json([
                'message' => 'Message submitted successfully',
                'data' => $contact
            ], 201);
        } catch (Exception $e) {

            Log::error('Contact Message Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Something went wrong',
            ], 500);
        }
    }
}

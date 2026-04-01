<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Login User
     *
     * Authenticate user and return API token.
     *
     * @group Authentication
     *
     * @bodyParam email string required User email. Example: test@example.com
     * @bodyParam password string required User password. Example: password123
     *
     * @response 200 {
     *  "message": "Login successful",
     *  "token": "1|xyz...",
     *  "user": {
     *      "id": 1,
     *      "name": "John Doe",
     *      "email": "test@example.com"
     *  }
     * }
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Get Logged-in User
     *
     * Returns authenticated user details.
     *
     * @group Authentication
     *
     * @authenticated
     *
     * @response 200 {
     *  "message": "User fetched successfully",
     *  "user": {
     *      "id": 1,
     *      "name": "John Doe",
     *      "email": "test@example.com"
     *  }
     * }
     */
    public function user(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'message' => 'User fetched successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * Logout User
     *
     * Revoke current access token.
     *
     * @group Authentication
     *
     * @authenticated
     *
     * @response 200 {
     *  "message": "Logged out successfully"
     * }
     */
    public function logout(Request $request)
    {
        // delete current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

 
}

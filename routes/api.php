<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CollaborationController;
use App\Http\Controllers\ContactMessageController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::get('categories', [CollaborationController::class, 'categories']);
Route::post('/collaboration/submit', [CollaborationController::class, 'store']);
Route::post('/contact/submit', [ContactMessageController::class, 'store']);
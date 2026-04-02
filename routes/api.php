<?php

use App\Http\Controllers\Admin\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CollaborationController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\TestimonialVideoController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login'])->name('login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::get('categories', [CollaborationController::class, 'categories']);
Route::post('/collaboration/submit', [CollaborationController::class, 'store']);
Route::post('/contact/submit', [ContactMessageController::class, 'store']);

Route::prefix('events')->group(function () {
    Route::get('/', [EventsController::class, 'index']);
    Route::get('/upcoming', [EventsController::class, 'getUpcomingEvents']);
    Route::post('/', [EventsController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/{uuid}', [EventsController::class, 'show']);
    Route::patch('/{uuid}', [EventsController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{uuid}', [EventsController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::prefix('gallery')->group(function () {
    Route::get('/', [MediaController::class, 'index']);
    Route::post('/', [MediaController::class, 'store'])->middleware('auth:sanctum');
    // Route::get('/{uuid}', [MediaController::class, 'show']);
    Route::patch('/{uuid}', [MediaController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{uuid}', [MediaController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::prefix('testimonial')->group(function () {
    Route::get('/', [TestimonialController::class, 'index']);
    Route::post('/', [TestimonialController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/{uuid}', [TestimonialController::class, 'show']);
    Route::patch('/{uuid}', [TestimonialController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{uuid}', [TestimonialController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::prefix('testimonial-video')->group(function () {
    Route::get('/', [TestimonialVideoController::class, 'index']);
    Route::post('/', [TestimonialVideoController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/{uuid}', [TestimonialVideoController::class, 'show']);
    Route::patch('/{uuid}', [TestimonialVideoController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{uuid}', [TestimonialVideoController::class, 'destroy'])->middleware('auth:sanctum');
});

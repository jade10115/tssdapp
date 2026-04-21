<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// // CSRF cookie route (for Sanctum)
// Route::get('/sanctum/csrf-cookie', function () {
//     return response()->json(['message' => 'CSRF cookie set']);
// });

// // Authentication routes
// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/logout', [AuthController::class, 'logout']);
// Route::get('/session', [AuthController::class, 'checkSession']);

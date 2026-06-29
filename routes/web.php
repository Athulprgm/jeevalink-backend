<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status'  => 'ok',
        'service' => 'Jeevalink API',
        'version' => '1.0.0',
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
    ]);
});

Route::get('/test-email', function () {
    return view('emails.volunteer_welcome', [
        'name' => 'John Doe',
        'email' => 'athulkrishnacpd@gmail.com',
        'password' => 'SecurePass123!',
        'loginUrl' => 'http://localhost:5173/login'
    ]);
});

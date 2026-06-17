<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\DonorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ─── Authentication Routes ──────────────────────────────────────────
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ─── Authenticated Routes Group ──────────────────────────────────────
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::patch('/auth/profile', [AuthController::class, 'profile']);
        Route::patch('/auth/toggle-availability', [AuthController::class, 'toggleAvailability']);
        Route::post('/auth/push-token', [AuthController::class, 'pushToken']);

        // ─── Blood Request Routes ───────────────────────────────────────────
        Route::post('/requests', [RequestController::class, 'create']);
        Route::get('/requests', [RequestController::class, 'index']);
        Route::patch('/requests/{id}/fulfill', [RequestController::class, 'fulfill']);
        Route::delete('/requests/{id}', [RequestController::class, 'destroy']);

        // ─── Donor Routes ───────────────────────────────────────────────────
        Route::get('/donors/search', [DonorController::class, 'search']);

        // ─── Notification Routes ────────────────────────────────────────────
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'read']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'readAll']);

        // ─── Complaint Submission Route ─────────────────────────────────────
        Route::post('/admin/complaints', [AdminController::class, 'fileComplaint']);

        // ─── Admin/Volunteer Shared Routes ──────────────────────────────────
        Route::middleware('jwt.role:admin,volunteer')->group(function () {
            Route::patch('/requests/{id}/verify', [RequestController::class, 'verify']);
        });

        // ─── Admin Only Routes ──────────────────────────────────────────────
        Route::middleware('jwt.role:admin')->group(function () {
            Route::get('/admin/complaints', [AdminController::class, 'getComplaints']);
            Route::patch('/admin/complaints/{id}/resolve', [AdminController::class, 'resolveComplaint']);
            Route::patch('/admin/users/{id}/status', [AdminController::class, 'updateUserStatus']);
            Route::get('/admin/users', [AdminController::class, 'getUsers']);
            Route::post('/admin/users/{id}/warn', [AdminController::class, 'warnUser']);
        });
    });
});

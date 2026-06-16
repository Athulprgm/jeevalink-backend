<?php

use App\Helpers\Router;
use App\Controllers\AuthController;
use App\Controllers\RequestController;
use App\Controllers\DonorController;
use App\Controllers\NotificationController;
use App\Controllers\AdminController;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

// ─── Authentication Routes ──────────────────────────────────────────
Router::post('/api/v1/auth/register', AuthController::class, 'register');
Router::post('/api/v1/auth/login', AuthController::class, 'login');

Router::get('/api/v1/auth/me', AuthController::class, 'me', [
    AuthMiddleware::class
]);
Router::patch('/api/v1/auth/profile', AuthController::class, 'profile', [
    AuthMiddleware::class
]);
Router::patch('/api/v1/auth/toggle-availability', AuthController::class, 'toggleAvailability', [
    AuthMiddleware::class
]);
Router::post('/api/v1/auth/push-token', AuthController::class, 'pushToken', [
    AuthMiddleware::class
]);

// ─── Blood Request Routes ───────────────────────────────────────────
Router::post('/api/v1/requests', RequestController::class, 'create', [
    AuthMiddleware::class
]);
Router::get('/api/v1/requests', RequestController::class, 'index', [
    AuthMiddleware::class
]);
Router::patch('/api/v1/requests/{id}/fulfill', RequestController::class, 'fulfill', [
    AuthMiddleware::class
]);
Router::patch('/api/v1/requests/{id}/verify', RequestController::class, 'verify', [
    AuthMiddleware::class
]);
Router::delete('/api/v1/requests/{id}', RequestController::class, 'delete', [
    AuthMiddleware::class
]);

// ─── Donor Routes ───────────────────────────────────────────────────
Router::get('/api/v1/donors/search', DonorController::class, 'search', [
    AuthMiddleware::class
]);

// ─── Notification Routes ────────────────────────────────────────────
Router::get('/api/v1/notifications', NotificationController::class, 'index', [
    AuthMiddleware::class
]);
Router::patch('/api/v1/notifications/{id}/read', NotificationController::class, 'read', [
    AuthMiddleware::class
]);
Router::patch('/api/v1/notifications/read-all', NotificationController::class, 'readAll', [
    AuthMiddleware::class
]);

// ─── Complaint & Admin Routes ───────────────────────────────────────
Router::post('/api/v1/admin/complaints', AdminController::class, 'fileComplaint', [
    AuthMiddleware::class
]);
Router::get('/api/v1/admin/complaints', AdminController::class, 'getComplaints', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
Router::patch('/api/v1/admin/complaints/{id}/resolve', AdminController::class, 'resolveComplaint', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
Router::patch('/api/v1/admin/users/{id}/status', AdminController::class, 'updateUserStatus', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
Router::get('/api/v1/admin/users', AdminController::class, 'getUsers', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
Router::post('/api/v1/admin/users/{id}/warn', AdminController::class, 'warnUser', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);

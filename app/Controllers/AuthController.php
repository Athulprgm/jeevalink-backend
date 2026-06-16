<?php

namespace App\Controllers;

use App\Models\User;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Config\JWT;

class AuthController
{
    /**
     * Handle user registration.
     *
     * @param array $request
     */
    public function register(array $request): void
    {
        $body = $request['body'] ?? [];
        
        $validator = new Validator($body);
        $validator->required(['full_name', 'email', 'mobile', 'password', 'role', 'city', 'district'])
                  ->email('email')
                  ->mobile('mobile')
                  ->enum('role', ['donor', 'volunteer', 'hospital', 'admin'])
                  ->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'N/A']);

        if (!$validator->passes()) {
            Response::error('Validation failed', $validator->getErrors(), 422);
        }

        // Check unique constraints
        if (User::findByEmail($body['email'])) {
            Response::error('Email already in use', ['email' => ['This email address is already registered.']], 409);
        }

        if (User::findByMobile($body['mobile'])) {
            Response::error('Mobile number already in use', ['mobile' => ['This mobile number is already registered.']], 409);
        }

        // Create user
        $userId = User::create($body);
        $user = User::findById($userId);

        if (!$user) {
            Response::error('Failed to create user account.', [], 500);
        }

        // Generate JWT
        $token = JWT::generateToken($userId, $user['role']);

        Response::success('User registered successfully.', [
            'token' => $token,
            'user' => $user
        ], 210);
    }

    /**
     * Handle user authentication login.
     *
     * @param array $request
     */
    public function login(array $request): void
    {
        $body = $request['body'] ?? [];
        
        $validator = new Validator($body);
        $validator->required(['credential', 'password']); // credential can be email or mobile

        if (!$validator->passes()) {
            Response::error('Validation failed', $validator->getErrors(), 422);
        }

        $credential = trim($body['credential']);
        $password = $body['password'];

        // Find user by email first, then mobile
        $user = null;
        if (filter_var($credential, FILTER_VALIDATE_EMAIL)) {
            $user = User::findByEmail($credential);
        } else {
            $user = User::findByMobile($credential);
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error('Invalid login credentials.', [], 401);
        }

        // Check if user is suspended/rejected
        if (in_array($user['status'], ['Suspended', 'Rejected'], true)) {
            Response::error("Your account has been {$user['status']}. Please contact support.", [], 403);
        }

        // Generate JWT
        $token = JWT::generateToken((int)$user['id'], $user['role']);
        
        // Retrieve sanitized profile data
        $profile = User::findById((int)$user['id']);

        Response::success('Authentication successful.', [
            'token' => $token,
            'user' => $profile
        ]);
    }

    /**
     * Get details of currently authenticated user.
     *
     * @param array $request
     */
    public function me(array $request): void
    {
        $userId = $request['user']['id'];
        $user = User::findById($userId);

        if (!$user) {
            Response::error('User profile not found.', [], 404);
        }

        Response::success('Profile retrieved successfully.', [
            'user' => $user
        ]);
    }

    /**
     * Update user profile information.
     *
     * @param array $request
     */
    public function profile(array $request): void
    {
        $userId = $request['user']['id'];
        $body = $request['body'] ?? [];

        $validator = new Validator($body);
        $validator->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'N/A'])
                  ->date('date_of_birth')
                  ->date('last_donated_date')
                  ->numeric('weight');

        if (!$validator->passes()) {
            Response::error('Validation failed', $validator->getErrors(), 422);
        }

        $updated = User::updateProfile($userId, $body);

        if (!$updated) {
            Response::error('No profile updates were made.', [], 400);
        }

        $user = User::findById($userId);
        Response::success('Profile updated successfully.', [
            'user' => $user
        ]);
    }

    /**
     * Toggle active availability state for blood donations.
     *
     * @param array $request
     */
    public function toggleAvailability(array $request): void
    {
        $userId = $request['user']['id'];
        $toggled = User::toggleAvailability($userId);

        if (!$toggled) {
            Response::error('Failed to update availability status.', [], 500);
        }

        $user = User::findById($userId);
        Response::success('Availability status updated successfully.', [
            'available_for_donation' => (bool)$user['available_for_donation']
        ]);
    }

    /**
     * Store push notification token.
     *
     * @param array $request
     */
    public function pushToken(array $request): void
    {
        $userId = $request['user']['id'];
        $body = $request['body'] ?? [];

        if (!isset($body['push_token'])) {
            Response::error('Missing parameter: push_token', [], 400);
        }

        $updated = User::updatePushToken($userId, $body['push_token']);

        if (!$updated) {
            Response::error('Failed to store push token.', [], 500);
        }

        Response::success('Push notification token updated successfully.');
    }
}

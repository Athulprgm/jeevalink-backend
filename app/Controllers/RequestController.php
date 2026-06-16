<?php

namespace App\Controllers;

use App\Models\BloodRequest;
use App\Models\User;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Services\NotificationService;

class RequestController
{
    /**
     * Create a new blood request.
     *
     * @param array $request
     */
    public function create(array $request): void
    {
        $userId = $request['user']['id'];
        $body = $request['body'] ?? [];

        $validator = new Validator($body);
        $validator->required(['patient_name', 'blood_group', 'units_required', 'hospital_name', 'city', 'district', 'contact_number', 'required_by_date'])
                  ->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])
                  ->enum('urgency_level', ['Normal', 'Urgent', 'Emergency SOS'])
                  ->numeric('units_required');

        if (!$validator->passes()) {
            Response::error('Validation failed', $validator->getErrors(), 422);
        }

        // Add creator ID
        $body['requested_by'] = $userId;
        
        // Admins and Volunteers auto-verify their requests, others need verification
        $body['verified'] = ($request['user']['role'] === 'admin' || $request['user']['role'] === 'volunteer') ? 1 : 0;

        $requestId = BloodRequest::create($body);
        $bloodRequest = BloodRequest::findById($requestId);

        if (!$bloodRequest) {
            Response::error('Failed to create blood request.', [], 500);
        }

        // Trigger asynchronous smart matching notifications
        NotificationService::notifyMatchingDonors($bloodRequest);

        Response::success('Blood request created successfully.', [
            'request' => $bloodRequest
        ], 201);
    }

    /**
     * Fetch requests with filters.
     *
     * @param array $request
     */
    public function index(array $request): void
    {
        // Parse filters from query parameters
        $filters = [
            'bloodGroup' => $_GET['bloodGroup'] ?? '',
            'district' => $_GET['district'] ?? '',
            'city' => $_GET['city'] ?? '',
            'urgencyLevel' => $_GET['urgencyLevel'] ?? '',
            'status' => $_GET['status'] ?? '',
            'verified' => $_GET['verified'] ?? ''
        ];

        $requests = BloodRequest::getAll($filters);

        Response::success('Requests retrieved successfully.', [
            'requests' => $requests
        ]);
    }

    /**
     * Mark a request as fulfilled.
     *
     * @param array $request
     * @param string $id
     */
    public function fulfill(array $request, string $id): void
    {
        $requestId = (int)$id;
        $userId = $request['user']['id'];

        $bloodRequest = BloodRequest::findById($requestId);

        if (!$bloodRequest) {
            Response::error('Blood request not found.', [], 404);
        }

        if ($bloodRequest['status'] === 'Fulfilled') {
            Response::error('This blood request has already been fulfilled.', [], 400);
        }

        // Verify that the person fulfilling is either the request creator, an admin, or a volunteer
        // Donors can fulfill their matching requests too.
        $fulfilled = BloodRequest::fulfill($requestId, $userId);

        if (!$fulfilled) {
            Response::error('Failed to update blood request status.', [], 500);
        }

        // Notify the requester
        $donor = User::findById($userId);
        NotificationService::notifyFulfillment($bloodRequest, $donor);

        // Reward the donor who fulfilled the request!
        // +100 reward points, +1 lives saved, +1 total donations
        User::incrementStats($userId, 'reward_points', 100);
        User::incrementStats($userId, 'lives_saved', 3); // A single blood donation can save up to 3 lives!
        User::incrementStats($userId, 'total_donations', 1);
        
        // Update user's last donated date to today
        User::updateProfile($userId, ['last_donated_date' => date('Y-m-d')]);

        // Send confirmation reward points notification
        NotificationService::notifyRewardPoints($userId, 100, "Donating blood and saving 3 lives!");

        $updatedRequest = BloodRequest::findById($requestId);

        Response::success('Blood request marked as fulfilled successfully.', [
            'request' => $updatedRequest
        ]);
    }

    /**
     * Verify a blood request.
     *
     * @param array $request
     * @param string $id
     */
    public function verify(array $request, string $id): void
    {
        $requestId = (int)$id;

        // Verify requesting user is admin or volunteer
        $role = $request['user']['role'];
        if ($role !== 'admin' && $role !== 'volunteer') {
            Response::error('Access denied. Administrator or Volunteer role required.', [], 403);
        }

        $bloodRequest = BloodRequest::findById($requestId);

        if (!$bloodRequest) {
            Response::error('Blood request not found.', [], 404);
        }

        $verified = BloodRequest::verify($requestId);

        if (!$verified) {
            Response::error('Failed to verify blood request.', [], 500);
        }

        $updatedRequest = BloodRequest::findById($requestId);

        Response::success('Blood request verified successfully.', [
            'request' => $updatedRequest
        ]);
    }

    /**
     * Delete/Reject a blood request.
     *
     * @param array $request
     * @param string $id
     */
    public function delete(array $request, string $id): void
    {
        $requestId = (int)$id;
        $userId = $request['user']['id'];
        $role = $request['user']['role'];

        $bloodRequest = BloodRequest::findById($requestId);

        if (!$bloodRequest) {
            Response::error('Blood request not found.', [], 404);
        }

        // Only request creator or admin can delete a request
        if ($bloodRequest['requested_by'] !== $userId && $role !== 'admin') {
            Response::error('You do not have permission to delete this request.', [], 403);
        }

        $deleted = BloodRequest::delete($requestId);

        if (!$deleted) {
            Response::error('Failed to delete blood request.', [], 500);
        }

        Response::success('Blood request removed successfully.');
    }
}

<?php

namespace App\Controllers;

use App\Models\Complaint;
use App\Models\User;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Services\NotificationService;

class AdminController
{
    /**
     * File a complaint against another user.
     * Accessible by authenticated users.
     *
     * @param array $request
     */
    public function fileComplaint(array $request): void
    {
        $userId = $request['user']['id'];
        $body = $request['body'] ?? [];

        $validator = new Validator($body);
        $validator->required(['target_id', 'reason'])
                  ->numeric('target_id');

        if (!$validator->passes()) {
            Response::error('Validation failed', $validator->getErrors(), 422);
        }

        $targetId = (int)$body['target_id'];

        if ($userId === $targetId) {
            Response::error('You cannot file a complaint against yourself.', [], 400);
        }

        $targetUser = User::findById($targetId);
        if (!$targetUser) {
            Response::error('Target user not found.', [], 404);
        }

        $complaintId = Complaint::create([
            'reporter_id' => $userId,
            'target_id' => $targetId,
            'reason' => $body['reason']
        ]);

        $complaint = Complaint::findById($complaintId);

        Response::success('Complaint filed successfully. Administrators will review it.', [
            'complaint' => $complaint
        ], 201);
    }

    /**
     * Get all filed complaints.
     * Admin only.
     *
     * @param array $request
     */
    public function getComplaints(array $request): void
    {
        $complaints = Complaint::getAll();
        Response::success('Complaints retrieved successfully.', [
            'complaints' => $complaints
        ]);
    }

    /**
     * Resolve a filed complaint.
     * Admin only.
     *
     * @param array $request
     * @param string $id
     */
    public function resolveComplaint(array $request, string $id): void
    {
        $complaintId = (int)$id;

        $complaint = Complaint::findById($complaintId);
        if (!$complaint) {
            Response::error('Complaint not found.', [], 404);
        }

        $resolved = Complaint::resolve($complaintId);
        if (!$resolved) {
            Response::error('Failed to resolve complaint.', [], 500);
        }

        Response::success('Complaint marked as resolved.');
    }

    /**
     * Update user status (Active/Suspended/Rejected).
     * Admin only.
     *
     * @param array $request
     * @param string $id
     */
    public function updateUserStatus(array $request, string $id): void
    {
        $targetUserId = (int)$id;
        $body = $request['body'] ?? [];

        $validator = new Validator($body);
        $validator->required(['status'])
                  ->enum('status', ['Active', 'Pending Approval', 'Suspended', 'Rejected']);

        if (!$validator->passes()) {
            Response::error('Validation failed', $validator->getErrors(), 422);
        }

        $targetUser = User::findById($targetUserId);
        if (!$targetUser) {
            Response::error('User not found.', [], 404);
        }

        $updated = User::updateStatus($targetUserId, $body['status']);
        if (!$updated) {
            Response::error('Failed to update user status.', [], 500);
        }

        // Send system notification about status change
        $statusMsg = "Your account status has been updated to: {$body['status']}.";
        if ($body['status'] === 'Suspended') {
            $statusMsg .= " You will not be able to log in until suspension is lifted.";
        }
        NotificationService::sendSystemWarning($targetUserId, $statusMsg);

        Response::success('User status updated successfully.', [
            'status' => $body['status']
        ]);
    }

    /**
     * Get all users in system.
     * Admin only.
     *
     * @param array $request
     */
    public function getUsers(array $request): void
    {
        $users = User::getAll();
        Response::success('Users retrieved successfully.', [
            'users' => $users
        ]);
    }

    /**
     * Warn a user.
     * Admin only.
     *
     * @param array $request
     * @param string $id
     */
    public function warnUser(array $request, string $id): void
    {
        $targetUserId = (int)$id;
        $body = $request['body'] ?? [];

        $validator = new Validator($body);
        $validator->required(['message']);

        if (!$validator->passes()) {
            Response::error('Validation failed', $validator->getErrors(), 422);
        }

        $targetUser = User::findById($targetUserId);
        if (!$targetUser) {
            Response::error('User not found.', [], 404);
        }

        NotificationService::sendSystemWarning($targetUserId, $body['message']);

        // Send push notification if token exists
        if (!empty($targetUser['expo_push_token'])) {
            \App\Services\FCMService::sendPushNotification(
                $targetUser['expo_push_token'],
                '⚠️ Official Warning Alert',
                $body['message'],
                ['type' => 'Warning']
            );
        }

        Response::success('Warning message dispatched to user successfully.');
    }
}

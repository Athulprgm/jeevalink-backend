<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\FCMService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * File a complaint against another user.
     * Accessible by authenticated users.
     */
    public function fileComplaint(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => []
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'target_id' => 'required|numeric',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $targetId = (int)$request->target_id;

        if ($user->id === $targetId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot file a complaint against yourself.',
                'errors' => []
            ], 400);
        }

        $targetUser = User::find($targetId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Target user not found.',
                'errors' => []
            ], 404);
        }

        Complaint::create([
            'reporter_id' => $user->id,
            'target_id' => $targetId,
            'reason' => $request->reason,
            'status' => 'Pending'
        ]);

        // In the original, it fetches the complaint by findById to get reporter/target names
        // But since we just created it, we can fetch it using a custom findById or standard Eloquent
        // Let's search by reporter and target and reason, or get the last inserted ID.
        // Wait, standard Eloquent create returns the instance with id!
        // So we can fetch using Complaint::findById($instance->id)
        $latestComplaint = Complaint::where('reporter_id', $user->id)
            ->where('target_id', $targetId)
            ->orderBy('id', 'desc')
            ->first();

        $complaintData = $latestComplaint ? Complaint::findById($latestComplaint->id) : null;

        return response()->json([
            'success' => true,
            'message' => 'Complaint filed successfully. Administrators will review it.',
            'data' => [
                'complaint' => $complaintData
            ]
        ], 201);
    }

    /**
     * Get all filed complaints.
     * Admin only.
     */
    public function getComplaints(Request $request)
    {
        $complaints = Complaint::getAll();
        return response()->json([
            'success' => true,
            'message' => 'Complaints retrieved successfully.',
            'data' => [
                'complaints' => $complaints
            ]
        ]);
    }

    /**
     * Resolve a filed complaint.
     * Admin only.
     */
    public function resolveComplaint(Request $request, $id)
    {
        $complaintId = (int)$id;

        $complaint = Complaint::find($complaintId);
        if (!$complaint) {
            return response()->json([
                'success' => false,
                'message' => 'Complaint not found.',
                'errors' => []
            ], 404);
        }

        $resolved = Complaint::resolve($complaintId);
        if (!$resolved) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve complaint.',
                'errors' => []
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Complaint marked as resolved.',
            'data' => []
        ]);
    }

    /**
     * Update user status (Active/Suspended/Rejected).
     * Admin only.
     */
    public function updateUserStatus(Request $request, $id)
    {
        $targetUserId = (int)$id;

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Active,Pending Approval,Suspended,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'errors' => []
            ], 404);
        }

        $updated = User::updateStatus($targetUserId, $request->status);
        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status.',
                'errors' => []
            ], 500);
        }

        // Send system notification about status change
        $statusMsg = "Your account status has been updated to: {$request->status}.";
        if ($request->status === 'Suspended') {
            $statusMsg .= " You will not be able to log in until suspension is lifted.";
        }
        NotificationService::sendSystemWarning($targetUserId, $statusMsg);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'data' => [
                'status' => $request->status
            ]
        ]);
    }

    /**
     * Get all users in system.
     * Admin only.
     */
    public function getUsers(Request $request)
    {
        $users = User::getAll();
        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => [
                'users' => $users
            ]
        ]);
    }

    /**
     * Warn a user.
     * Admin only.
     */
    public function warnUser(Request $request, $id)
    {
        $targetUserId = (int)$id;

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'errors' => []
            ], 404);
        }

        NotificationService::sendSystemWarning($targetUserId, $request->message);

        // Send push notification if token exists
        if (!empty($targetUser->expo_push_token)) {
            FCMService::sendPushNotification(
                $targetUser->expo_push_token,
                '⚠️ Official Warning Alert',
                $request->message,
                ['type' => 'Warning']
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Warning message dispatched to user successfully.',
            'data' => []
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for currently authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => []
            ], 401);
        }

        $notifications = Notification::getByRecipient($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => [
                'notifications' => $notifications
            ]
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function read(Request $request, $id)
    {
        $notificationId = (int)$id;
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => []
            ], 401);
        }

        $updated = Notification::markAsRead($notificationId, $user->id);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read.',
                'errors' => []
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read successfully.',
            'data' => []
        ]);
    }

    /**
     * Mark all notifications for the user as read.
     */
    public function readAll(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => []
            ], 401);
        }

        $updated = Notification::markAllAsRead($user->id);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read.',
                'errors' => []
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read successfully.',
            'data' => []
        ]);
    }
}

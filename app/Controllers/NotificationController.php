<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Helpers\Response;

class NotificationController
{
    /**
     * Get all notifications for currently authenticated user.
     *
     * @param array $request
     */
    public function index(array $request): void
    {
        $userId = $request['user']['id'];
        $notifications = Notification::getByRecipient($userId);

        Response::success('Notifications retrieved successfully.', [
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark a specific notification as read.
     *
     * @param array $request
     * @param string $id
     */
    public function read(array $request, string $id): void
    {
        $notificationId = (int)$id;
        $userId = $request['user']['id'];

        $updated = Notification::markAsRead($notificationId, $userId);

        if (!$updated) {
            Response::error('Failed to mark notification as read.', [], 400);
        }

        Response::success('Notification marked as read successfully.');
    }

    /**
     * Mark all notifications for the user as read.
     *
     * @param array $request
     */
    public function readAll(array $request): void
    {
        $userId = $request['user']['id'];

        $updated = Notification::markAllAsRead($userId);

        if (!$updated) {
            Response::error('Failed to mark notifications as read.', [], 400);
        }

        Response::success('All notifications marked as read successfully.');
    }
}

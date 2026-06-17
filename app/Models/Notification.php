<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Notification
{
    /**
     * Create a new notification.
     *
     * @param array $data
     * @return int Inserted ID
     */
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        
        $sql = "INSERT INTO notifications (recipient_id, title, message, type, is_read, created_at) 
                VALUES (:recipient_id, :title, :message, :type, FALSE, CURRENT_TIMESTAMP)";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':recipient_id' => $data['recipient_id'],
            ':title' => $data['title'],
            ':message' => $data['message'],
            ':type' => $data['type']
        ]);
        
        return (int)$db->lastInsertId('notifications_id_seq');
    }

    /**
     * Fetch notifications for a recipient.
     *
     * @param int $recipientId
     * @return array
     */
    public static function getByRecipient(int $recipientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE recipient_id = ? ORDER BY created_at DESC");
        $stmt->execute([$recipientId]);
        return $stmt->fetchAll();
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id
     * @param int $recipientId
     * @return bool
     */
    public static function markAsRead(int $id, int $recipientId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND recipient_id = ?");
        return $stmt->execute([$id, $recipientId]);
    }

    /**
     * Mark all notifications for a recipient as read.
     *
     * @param int $recipientId
     * @return bool
     */
    public static function markAllAsRead(int $recipientId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE recipient_id = ?");
        return $stmt->execute([$recipientId]);
    }
}

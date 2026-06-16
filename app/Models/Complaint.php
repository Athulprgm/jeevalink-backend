<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Complaint
{
    /**
     * Create a new complaint.
     *
     * @param array $data
     * @return int Inserted ID
     */
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        
        $sql = "INSERT INTO complaints (reporter_id, target_id, reason, status, created_at) 
                VALUES (:reporter_id, :target_id, :reason, 'Pending', CURRENT_TIMESTAMP)";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':reporter_id' => $data['reporter_id'],
            ':target_id' => $data['target_id'],
            ':reason' => $data['reason']
        ]);
        
        return (int)$db->lastInsertId();
    }

    /**
     * Find complaint by ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        
        $sql = "SELECT c.*, 
                       u1.full_name AS reporter_name, 
                       u2.full_name AS target_name 
                FROM complaints c
                JOIN users u1 ON c.reporter_id = u1.id
                JOIN users u2 ON c.target_id = u2.id
                WHERE c.id = ? LIMIT 1";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $complaint = $stmt->fetch();
        return $complaint ?: null;
    }

    /**
     * Fetch all complaints in the system.
     *
     * @return array
     */
    public static function getAll(): array
    {
        $db = Database::getConnection();
        
        $sql = "SELECT c.id, c.reporter_id, c.target_id, c.reason, c.status, c.created_at,
                       u1.full_name AS reporter_name, 
                       u2.full_name AS target_name 
                FROM complaints c
                JOIN users u1 ON c.reporter_id = u1.id
                JOIN users u2 ON c.target_id = u2.id
                ORDER BY c.created_at DESC";
                
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Resolve a complaint.
     *
     * @param int $id
     * @return bool
     */
    public static function resolve(int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE complaints SET status = 'Resolved' WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

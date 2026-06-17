<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    /**
     * Create a new user.
     *
     * @param array $data
     * @return int Inserted ID
     */
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        
        $sql = "INSERT INTO users (
                    full_name, email, mobile, password_hash, role, blood_group, 
                    city, district, address, weight, date_of_birth, last_donated_date, 
                    profile_picture, available_for_donation, status
                ) VALUES (
                    :full_name, :email, :mobile, :password_hash, :role, :blood_group, 
                    :city, :district, :address, :weight, :date_of_birth, :last_donated_date, 
                    :profile_picture, :available_for_donation, :status
                )";

        $stmt = $db->prepare($sql);
        
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Hospitals start as 'Pending Approval', others are 'Active' by default
        $status = ($data['role'] === 'hospital') ? 'Pending Approval' : 'Active';
        $available = isset($data['available_for_donation']) ? (bool)$data['available_for_donation'] : true;

        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':email' => $data['email'],
            ':mobile' => $data['mobile'],
            ':password_hash' => $passwordHash,
            ':role' => $data['role'] ?? 'donor',
            ':blood_group' => $data['blood_group'] ?? 'N/A',
            ':city' => $data['city'],
            ':district' => $data['district'],
            ':address' => $data['address'] ?? null,
            ':weight' => $data['weight'] ?? null,
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':last_donated_date' => $data['last_donated_date'] ?? null,
            ':profile_picture' => $data['profile_picture'] ?? null,
            ':available_for_donation' => $available,
            ':status' => $status
        ]);

        return (int)$db->lastInsertId('users_id_seq');
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return array|null
     */
    public static function findByEmail(string $email): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Find a user by mobile number.
     *
     * @param string $mobile
     * @return array|null
     */
    public static function findByMobile(string $mobile): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE mobile = ? LIMIT 1");
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, full_name, email, mobile, role, blood_group, city, district, address, weight, date_of_birth, last_donated_date, profile_picture, available_for_donation, reward_points, lives_saved, total_donations, status, created_at, updated_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Update user profile data.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateProfile(int $id, array $data): bool
    {
        $db = Database::getConnection();
        
        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'full_name', 'blood_group', 'city', 'district', 
            'address', 'weight', 'date_of_birth', 'last_donated_date', 
            'profile_picture'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Toggle availability for donation.
     *
     * @param int $id
     * @return bool
     */
    public static function toggleAvailability(int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET available_for_donation = NOT available_for_donation WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Update Expo push notification token.
     *
     * @param int $id
     * @param string|null $token
     * @return bool
     */
    public static function updatePushToken(int $id, ?string $token): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET expo_push_token = ? WHERE id = ?");
        return $stmt->execute([$token, $id]);
    }

    /**
     * Update user status (Active, Suspended, etc.).
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public static function updateStatus(int $id, string $status): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Increment a numeric statistic column.
     *
     * @param int $id
     * @param string $field
     * @param int $amount
     * @return bool
     */
    public static function incrementStats(int $id, string $field, int $amount = 1): bool
    {
        $db = Database::getConnection();
        $allowed = ['reward_points', 'lives_saved', 'total_donations'];
        if (!in_array($field, $allowed, true)) {
            return false;
        }
        $stmt = $db->prepare("UPDATE users SET {$field} = {$field} + ? WHERE id = ?");
        return $stmt->execute([$amount, $id]);
    }

    /**
     * Search compatible donors.
     *
     * @param array $filters
     * @param int|null $excludeId
     * @return array
     */
    public static function searchDonors(array $filters, ?int $excludeId = null): array
    {
        $db = Database::getConnection();
        
        $sql = "SELECT id, full_name, email, mobile, blood_group, city, district, 
                       profile_picture, available_for_donation, reward_points, 
                       lives_saved, total_donations, status, last_donated_date 
                FROM users 
                WHERE available_for_donation = 1 
                  AND status = 'Active'";
        
        $params = [];

        if (!empty($filters['bloodGroup'])) {
            $sql .= " AND blood_group = :blood_group";
            $params[':blood_group'] = $filters['bloodGroup'];
        }

        if (!empty($filters['district'])) {
            $sql .= " AND district = :district";
            $params[':district'] = $filters['district'];
        }

        if (!empty($filters['city'])) {
            $sql .= " AND city = :city";
            $params[':city'] = $filters['city'];
        }

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch all users in the system (Admin dashboard).
     *
     * @return array
     */
    public static function getAll(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT id, full_name, email, mobile, role, blood_group, city, district, address, reward_points, lives_saved, total_donations, status, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
}

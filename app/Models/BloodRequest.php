<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class BloodRequest
{
    /**
     * Create a new blood request.
     *
     * @param array $data
     * @return int Inserted ID
     */
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        
        $sql = "INSERT INTO blood_requests (
                    requested_by, patient_name, blood_group, units_required, 
                    hospital_name, hospital_address, city, district, location, 
                    contact_number, contact_person_name, required_by_date, 
                    urgency_level, additional_notes, status, verified
                ) VALUES (
                    :requested_by, :patient_name, :blood_group, :units_required, 
                    :hospital_name, :hospital_address, :city, :district, :location, 
                    :contact_number, :contact_person_name, :required_by_date, 
                    :urgency_level, :additional_notes, 'Pending', :verified
                )";

        $stmt = $db->prepare($sql);
        
        // If urgency level is Emergency SOS, verified is automatically true or false depending on business rules (let's keep false unless volunteer verifies)
        $stmt->execute([
            ':requested_by' => $data['requested_by'],
            ':patient_name' => $data['patient_name'],
            ':blood_group' => $data['blood_group'],
            ':units_required' => (int)$data['units_required'],
            ':hospital_name' => $data['hospital_name'],
            ':hospital_address' => $data['hospital_address'] ?? null,
            ':city' => $data['city'],
            ':district' => $data['district'],
            ':location' => $data['location'] ?? null,
            ':contact_number' => $data['contact_number'],
            ':contact_person_name' => $data['contact_person_name'] ?? null,
            ':required_by_date' => $data['required_by_date'],
            ':urgency_level' => $data['urgency_level'] ?? 'Normal',
            ':additional_notes' => $data['additional_notes'] ?? null,
            ':verified' => isset($data['verified']) ? (int)$data['verified'] : 0
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Find blood request by ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        
        $sql = "SELECT br.*, u.full_name AS requester_name, u.email AS requester_email 
                FROM blood_requests br
                JOIN users u ON br.requested_by = u.id
                WHERE br.id = ? LIMIT 1";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $request = $stmt->fetch();
        return $request ?: null;
    }

    /**
     * Get all requests matching the given filters.
     *
     * @param array $filters
     * @return array
     */
    public static function getAll(array $filters): array
    {
        $db = Database::getConnection();
        
        $sql = "SELECT br.*, u.full_name AS requester_name, u.email AS requester_email, u.profile_picture AS requester_picture 
                FROM blood_requests br
                JOIN users u ON br.requested_by = u.id
                WHERE 1 = 1";
                
        $params = [];

        if (!empty($filters['bloodGroup'])) {
            $sql .= " AND br.blood_group = :blood_group";
            $params[':blood_group'] = $filters['bloodGroup'];
        }

        if (!empty($filters['district'])) {
            $sql .= " AND br.district = :district";
            $params[':district'] = $filters['district'];
        }

        if (!empty($filters['city'])) {
            $sql .= " AND br.city = :city";
            $params[':city'] = $filters['city'];
        }

        if (!empty($filters['urgencyLevel'])) {
            $sql .= " AND br.urgency_level = :urgency_level";
            $params[':urgency_level'] = $filters['urgencyLevel'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND br.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (isset($filters['verified']) && $filters['verified'] !== '') {
            $sql .= " AND br.verified = :verified";
            $params[':verified'] = (bool)$filters['verified'] ? 1 : 0;
        }

        $sql .= " ORDER BY br.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Mark a blood request as fulfilled.
     *
     * @param int $id
     * @param int $fulfilledBy
     * @return bool
     */
    public static function fulfill(int $id, int $fulfilledBy): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE blood_requests SET status = 'Fulfilled', fulfilled_by = ? WHERE id = ?");
        return $stmt->execute([$fulfilledBy, $id]);
    }

    /**
     * Verify a blood request.
     *
     * @param int $id
     * @return bool
     */
    public static function verify(int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE blood_requests SET verified = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Delete a blood request.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM blood_requests WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

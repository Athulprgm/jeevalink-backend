<?php

namespace App\Services;

use App\Models\User;

class MatchingService
{
    /**
     * Find matching active donors for a blood request.
     *
     * @param array $requestData
     * @return array
     */
    public static function findMatches(array $requestData): array
    {
        $filters = [
            'bloodGroup' => $requestData['blood_group'],
            'district' => $requestData['district']
        ];
        
        $excludeId = $requestData['requested_by'] ?? null;

        // Perform search based on blood group and district matching
        return User::searchDonors($filters, $excludeId);
    }
}

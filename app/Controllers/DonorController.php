<?php

namespace App\Controllers;

use App\Models\User;
use App\Helpers\Response;

class DonorController
{
    /**
     * Search compatible active donors in the platform.
     *
     * @param array $request
     */
    public function search(array $request): void
    {
        $userId = $request['user']['id'];

        $filters = [
            'bloodGroup' => $_GET['bloodGroup'] ?? '',
            'district' => $_GET['district'] ?? '',
            'city' => $_GET['city'] ?? ''
        ];

        // Search donors matching filters, excluding currently logged in requester
        $donors = User::searchDonors($filters, $userId);

        Response::success('Donors matching criteria retrieved successfully.', [
            'donors' => $donors
        ]);
    }
}

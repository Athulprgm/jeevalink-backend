<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DonorController extends Controller
{
    /**
     * Search compatible active donors in the platform.
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => []
            ], 401);
        }

        $filters = [
            'bloodGroup' => $request->query('bloodGroup', ''),
            'district' => $request->query('district', ''),
            'city' => $request->query('city', '')
        ];

        // Search donors matching filters, excluding currently logged in requester
        $donors = User::searchDonors($filters, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Donors matching criteria retrieved successfully.',
            'data' => [
                'donors' => $donors
            ]
        ]);
    }
}

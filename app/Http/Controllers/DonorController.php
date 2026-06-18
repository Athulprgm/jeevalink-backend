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

    /**
     * Get counts of live active available donors grouped by blood group.
     */
    public function liveCount(Request $request)
    {
        $counts = User::where('available_for_donation', true)
            ->where('status', 'Active')
            ->selectRaw('blood_group, count(*) as count')
            ->groupBy('blood_group')
            ->pluck('count', 'blood_group')
            ->toArray();

        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $responseCounts = [];
        foreach ($bloodGroups as $bg) {
            $responseCounts[$bg] = $counts[$bg] ?? 0;
        }

        return response()->json([
            'success' => true,
            'message' => 'Live donor counts retrieved successfully.',
            'data' => [
                'counts' => $responseCounts
            ]
        ]);
    }

    /**
     * Save the donation eligibility status of the authenticated donor.
     */
    public function saveEligibility(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => []
            ], 401);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'eligibility_status' => 'required|in:Eligible,Ineligible',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $dbUser = User::find($user->id);
        $dbUser->eligibility_status = $request->eligibility_status;
        $dbUser->eligibility_checked_at = now();

        if ($request->eligibility_status === 'Ineligible') {
            $dbUser->available_for_donation = false;
        } else {
            $dbUser->available_for_donation = true;
        }
        $dbUser->save();

        $profile = User::findById($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Donation eligibility status saved successfully.',
            'data' => [
                'user' => $profile
            ]
        ]);
    }
}

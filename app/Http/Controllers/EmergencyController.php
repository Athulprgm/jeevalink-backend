<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmergencyRequest;
use App\Models\EmergencyResponse;
use App\Jobs\SendFcmAlertJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EmergencyController extends Controller
{
    /**
     * Save user's FCM token and location status.
     * POST /api/save-fcm-token
     */
    public function saveFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notification_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user.'
            ], 401);
        }

        $user->fcm_token = $request->fcm_token;
        if ($request->has('latitude')) {
            $user->latitude = $request->latitude;
        }
        if ($request->has('longitude')) {
            $user->longitude = $request->longitude;
        }
        if ($request->has('notification_enabled')) {
            $user->notification_enabled = (bool)$request->notification_enabled;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'FCM push settings updated successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'fcm_token' => $user->fcm_token,
                    'latitude' => $user->latitude,
                    'longitude' => $user->longitude,
                    'notification_enabled' => $user->notification_enabled,
                ]
            ]
        ]);
    }

    /**
     * Create an emergency blood alert request.
     * POST /api/emergency/request
     */
    public function createRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blood_group' => 'required|string|max:5',
            'units_required' => 'required|integer|min:1',
            'patient_name' => 'required|string|max:255',
            'hospital_name' => 'required|string|max:255',
            'district' => 'required|string|max:100',
            'contact_number' => 'required|string|max:20',
            'emergency_message' => 'nullable|string',
            'priority' => 'required|string|in:critical,high,normal',
            'expires_at' => 'nullable|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius' => 'nullable|numeric', // in km
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user.'
            ], 401);
        }

        $expiresAt = $request->expires_at ? new \DateTime($request->expires_at) : now()->addHours(4);
        $radius = $request->radius ?? 15; // default 15km

        // Create the request
        $emergencyRequest = EmergencyRequest::create([
            'requester_id' => $user->id,
            'blood_group' => $request->blood_group,
            'units_required' => (int)$request->units_required,
            'patient_name' => $request->patient_name,
            'hospital_name' => $request->hospital_name,
            'district' => $request->district,
            'contact_number' => $request->contact_number,
            'emergency_message' => $request->emergency_message,
            'priority' => $request->priority,
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'latitude' => $request->latitude ?? $user->latitude,
            'longitude' => $request->longitude ?? $user->longitude,
        ]);

        // Find nearby matching donors
        $lat = $request->latitude ?? $user->latitude;
        $lng = $request->longitude ?? $user->longitude;
        
        $nearbyDonors = User::getNearbyDonors($lat, $lng, $radius, $request->blood_group, $request->district, $user->id);
        $donorIds = $nearbyDonors->pluck('id')->toArray();

        // Dispatch notifications via queue
        if (!empty($donorIds)) {
            SendFcmAlertJob::dispatch($emergencyRequest->id, $donorIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Emergency blood alert request generated and notification job queued.',
            'data' => [
                'request' => $emergencyRequest,
                'target_donors_count' => count($donorIds)
            ]
        ], 201);
    }

    /**
     * Retrieve emergency requests history.
     * GET /api/emergency/history
     */
    public function getHistory(Request $request)
    {
        $query = EmergencyRequest::with('requester:id,full_name,email,role');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->has('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }
        if ($request->has('district')) {
            $query->where('district', $request->district);
        }

        $history = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'requests' => $history
            ]
        ]);
    }

    /**
     * Retrieve specific emergency request details with donor responses.
     * GET /api/emergency/details/{id}
     */
    public function getDetails($id)
    {
        $emergencyRequest = EmergencyRequest::with(['requester:id,full_name,email,role'])->find($id);

        if (!$emergencyRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency request not found.'
            ], 404);
        }

        // Get donor responses
        $responses = EmergencyResponse::with('donor:id,full_name,mobile,blood_group,city,district')
            ->where('request_id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'request' => $emergencyRequest,
                'responses' => $responses
            ]
        ]);
    }

    /**
     * Donor accepts an emergency request.
     * POST /api/emergency/accept
     */
    public function acceptRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:emergency_requests,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user.'
            ], 401);
        }

        $emergencyRequest = EmergencyRequest::find($request->request_id);
        if ($emergencyRequest->status === 'expired') {
            return response()->json([
                'success' => false,
                'message' => 'This emergency request has already expired.'
            ], 400);
        }

        // Create or update response
        $response = EmergencyResponse::updateOrCreate(
            [
                'request_id' => $request->request_id,
                'donor_id' => $user->id,
            ],
            [
                'response_status' => 'accepted'
            ]
        );

        // Update emergency request status if needed (e.g. accepted)
        if ($emergencyRequest->status === 'pending') {
            $emergencyRequest->status = 'accepted';
            $emergencyRequest->save();
        }

        // Award gamified reward points to the donor (+20 reward points)
        $user->increment('reward_points', 20);
        $user->increment('lives_saved', 1);

        return response()->json([
            'success' => true,
            'message' => 'Emergency request accepted. Thank you for your support! +20 Reward Points added.',
            'data' => [
                'response' => $response,
                'user' => [
                    'id' => $user->id,
                    'reward_points' => $user->reward_points,
                    'lives_saved' => $user->lives_saved
                ]
            ]
        ]);
    }

    /**
     * Donor rejects an emergency request.
     * POST /api/emergency/reject
     */
    public function rejectRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:emergency_requests,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user.'
            ], 401);
        }

        $response = EmergencyResponse::updateOrCreate(
            [
                'request_id' => $request->request_id,
                'donor_id' => $user->id,
            ],
            [
                'response_status' => 'rejected'
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Emergency request rejected.',
            'data' => [
                'response' => $response
            ]
        ]);
    }

    /**
     * Fetch nearby donors.
     * GET /api/emergency/nearby-donors
     */
    public function getNearbyDonors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blood_group' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|numeric',
            'district' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $radius = $request->radius ?? 15;
        $nearbyDonors = User::getNearbyDonors(
            $request->latitude,
            $request->longitude,
            $radius,
            $request->blood_group,
            $request->district,
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'donors' => $nearbyDonors
            ]
        ]);
    }

    /**
     * Get live donor count matching criteria.
     * GET /api/emergency/live-donor-count
     */
    public function getLiveDonorCount(Request $request)
    {
        $query = User::where('available_for_donation', true)
            ->where('status', 'Active')
            ->where('role', 'donor');

        if ($request->has('blood_group') && $request->blood_group !== 'N/A') {
            $query->where('blood_group', $request->blood_group);
        }

        if ($request->has('district')) {
            $query->where('district', $request->district);
        }

        $count = $query->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count
            ]
        ]);
    }
}

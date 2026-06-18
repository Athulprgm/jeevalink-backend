<?php

namespace App\Jobs;

use App\Models\EmergencyRequest;
use App\Models\User;
use App\Models\Notification as DbNotification;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFcmAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $requestId;
    protected $donorIds;

    /**
     * Create a new job instance.
     *
     * @param int $requestId
     * @param array $donorIds
     */
    public function __construct(int $requestId, array $donorIds)
    {
        $this->requestId = $requestId;
        $this->donorIds = $donorIds;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService): void
    {
        $request = EmergencyRequest::find($this->requestId);
        if (!$request) {
            Log::warning("SendFcmAlertJob failed: Emergency request not found for ID: " . $this->requestId);
            return;
        }

        $donors = User::whereIn('id', $this->donorIds)
            ->where('available_for_donation', true)
            ->where('status', 'Active')
            ->get();

        if ($donors->isEmpty()) {
            Log::info("SendFcmAlertJob: No active donors found to alert for request ID: " . $this->requestId);
            return;
        }

        $title = "🚨 Emergency Blood Alert: " . $request->blood_group;
        $body = "Emergency request at " . $request->hospital_name . " for " . $request->units_required . " units of " . $request->blood_group . " blood.";
        
        $data = [
            'type' => 'emergency_request',
            'request_id' => (string)$request->id,
            'blood_group' => $request->blood_group,
            'hospital_name' => $request->hospital_name,
            'priority' => $request->priority,
        ];

        $tokens = [];
        
        foreach ($donors as $donor) {
            // 1. Create a local database notification record for the user
            try {
                DbNotification::create([
                    'recipient_id' => $donor->id,
                    'title' => $title,
                    'message' => $body . " " . ($request->emergency_message ? "Message: " . $request->emergency_message : ""),
                    'type' => 'SOS',
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to insert database notification for user " . $donor->id . ": " . $e->getMessage());
            }

            // 2. Gather FCM token if present
            if ($donor->fcm_token) {
                $tokens[] = $donor->fcm_token;
            }
        }

        if (!empty($tokens)) {
            Log::info("Sending multicast emergency FCM push to " . count($tokens) . " tokens.");
            $firebaseService->sendMulticast($tokens, $title, $body, $data);
        } else {
            Log::info("SendFcmAlertJob: No device tokens found for matching donors.");
        }
    }
}

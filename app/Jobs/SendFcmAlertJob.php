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

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300]; // 1 minute, 5 minutes
    }

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

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

            // 2. Gather FCM token if present and send notification
            if ($donor->fcm_token) {
                $firebaseService->sendNotification($donor->fcm_token, $title, $body, $data, $donor->id);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendFcmAlertJob completely failed for request ID {$this->requestId} after {$this->tries} attempts. Error: " . $exception->getMessage());
    }
}

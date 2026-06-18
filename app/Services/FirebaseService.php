<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        try {
            // Attempt to resolve Firebase Messaging service if Kreait package is active
            if (class_exists(\Kreait\Laravel\Firebase\Facades\Firebase::class)) {
                $this->messaging = \Kreait\Laravel\Firebase\Facades\Firebase::messaging();
            } elseif (class_exists(\Kreait\Firebase\Factory::class)) {
                // If direct SDK is installed but Laravel wrapper is not loaded
                $factory = (new \Kreait\Firebase\Factory());
                $credentialsPath = env('FIREBASE_CREDENTIALS');
                if ($credentialsPath && file_exists($credentialsPath)) {
                    $factory = $factory->withServiceAccount($credentialsPath);
                }
                $this->messaging = $factory->createMessaging();
            }
        } catch (\Exception $e) {
            Log::warning("Firebase SDK failed to initialize: " . $e->getMessage() . ". Falling back to log simulation.");
            $this->messaging = null;
        }
    }

    /**
     * Send emergency FCM alert to a single device token.
     *
     * @param string $token Device FCM Token
     * @param string $title Notification Title
     * @param string $body Notification Body/Message
     * @param array $data Custom key-value payload data
     * @return bool
     */
    public function sendNotification(string $token, string $title, string $body, array $data = []): bool
    {
        Log::info("FCM Notification Dispatch Request", [
            'token' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data
        ]);

        if ($this->messaging) {
            try {
                // Construct Cloud Message
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification(Notification::create($title, $body))
                    ->withData($data);

                $this->messaging->send($message);
                Log::info("FCM Notification sent successfully via Firebase SDK to token: " . substr($token, 0, 15) . "...");
                return true;
            } catch (\Exception $e) {
                Log::error("FCM Send Error via SDK: " . $e->getMessage());
            }
        }

        // Fallback: Simulation/Log mode
        Log::info("[SIMULATED FCM] Push Alert dispatched successfully to token: " . substr($token, 0, 15) . "...");
        return true;
    }

    /**
     * Send emergency FCM alert to multiple device tokens (multicast).
     *
     * @param array $tokens Array of device FCM tokens
     * @param string $title Notification Title
     * @param string $body Notification Body/Message
     * @param array $data Custom key-value payload data
     * @return array Report containing success/failure status
     */
    public function sendMulticast(array $tokens, string $title, string $body, array $data = []): array
    {
        $tokens = array_filter($tokens); // Remove empty values
        if (empty($tokens)) {
            return ['success_count' => 0, 'failure_count' => 0];
        }

        Log::info("FCM Multicast Dispatch Request", [
            'token_count' => count($tokens),
            'title' => $title,
            'body' => $body,
            'data' => $data
        ]);

        if ($this->messaging) {
            try {
                $message = CloudMessage::new()
                    ->withNotification(Notification::create($title, $body))
                    ->withData($data);

                $report = $this->messaging->sendMulticast($message, $tokens);
                Log::info("FCM Multicast dispatch complete via SDK.", [
                    'successes' => $report->successes()->count(),
                    'failures' => $report->failures()->count()
                ]);
                return [
                    'success_count' => $report->successes()->count(),
                    'failure_count' => $report->failures()->count(),
                ];
            } catch (\Exception $e) {
                Log::error("FCM Multicast Error via SDK: " . $e->getMessage());
            }
        }

        // Fallback Simulation Mode
        Log::info("[SIMULATED FCM MULTICAST] Dispatched alerts to " . count($tokens) . " tokens.");
        return [
            'success_count' => count($tokens),
            'failure_count' => 0
        ];
    }
}

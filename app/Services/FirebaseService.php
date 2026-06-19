<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class FirebaseService
{
    /**
     * Get OAuth2 Bearer token for FCM v1 API using the Service Account JSON.
     * Generates a short-lived JWT and exchanges it for an access token.
     *
     * @return string|null
     */
    private function getAccessToken(): ?string
    {
        $credentialsPath = env('FIREBASE_CREDENTIALS');
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            Log::error("Firebase Credentials missing. Expected path: {$credentialsPath}");
            return null;
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);
        if (!$credentials || !isset($credentials['client_email']) || !isset($credentials['private_key'])) {
            Log::error("Invalid Firebase credentials file format.");
            return null;
        }

        $now = time();
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;

        $signature = '';
        if (!openssl_sign($signatureInput, $signature, $credentials['private_key'], 'sha256')) {
            Log::error("Failed to sign JWT for FCM.");
            return null;
        }

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $signatureInput . "." . $base64UrlSignature;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        Log::error("Failed to get FCM access token. HTTP Code: {$httpCode}, Response: {$response}");
        return null;
    }

    /**
     * Send emergency FCM alert to a single device token using FCM v1 API.
     *
     * @param string $token Device FCM Token
     * @param string $title Notification Title
     * @param string $body Notification Body/Message
     * @param array $data Custom key-value payload data
     * @param int|null $userId Optional user ID for logging
     * @return bool
     */
    public function sendNotification(string $token, string $title, string $body, array $data = [], ?int $userId = null): bool
    {
        $projectId = env('FIREBASE_PROJECT_ID');
        if (!$projectId) {
            $this->logResult($userId, $token, $title, $body, $data, 'simulated', null, 'Missing FIREBASE_PROJECT_ID');
            return true; // Simulate success
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            $this->logResult($userId, $token, $title, $body, $data, 'failed', null, 'Auth token failed');
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'jeevalink_urgent',
                        'sound' => 'default',
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ]
                    ]
                ],
                'data' => (object)$data // ensure it's an object even if empty
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode === 200) {
            $messageId = $responseData['name'] ?? null;
            $this->logResult($userId, $token, $title, $body, $data, 'sent', $messageId, null);
            return true;
        }

        // Handle errors (e.g., token unregistered)
        $errorMsg = $responseData['error']['message'] ?? $response;
        $errorCode = $responseData['error']['details'][0]['errorCode'] ?? null;

        if ($errorCode === 'UNREGISTERED' || $errorCode === 'INVALID_ARGUMENT') {
            // Clean up invalid tokens
            if ($userId) {
                User::where('id', $userId)->update(['fcm_token' => null]);
            } else {
                User::where('fcm_token', $token)->update(['fcm_token' => null]);
            }
            $this->logResult($userId, $token, $title, $body, $data, 'invalid_token', null, $errorMsg);
        } else {
            $this->logResult($userId, $token, $title, $body, $data, 'failed', null, $errorMsg);
        }

        Log::error("FCM Send Error [HTTP {$httpCode}]: {$response}");
        return false;
    }

    /**
     * Send emergency FCM alert to multiple device tokens.
     * FCM v1 does not have a native multicast endpoint. We loop through tokens.
     *
     * @param array $tokens Array of device FCM tokens
     * @param string $title Notification Title
     * @param string $body Notification Body/Message
     * @param array $data Custom key-value payload data
     * @return array Report containing success/failure status
     */
    public function sendMulticast(array $tokens, string $title, string $body, array $data = []): array
    {
        $tokens = array_filter(array_unique($tokens));
        if (empty($tokens)) {
            return ['success_count' => 0, 'failure_count' => 0];
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($tokens as $token) {
            $user = User::where('fcm_token', $token)->first();
            $userId = $user ? $user->id : null;
            
            $success = $this->sendNotification($token, $title, $body, $data, $userId);
            if ($success) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ];
    }

    /**
     * Log notification delivery attempt to database.
     */
    private function logResult(?int $userId, string $token, string $title, string $body, array $data, string $status, ?string $messageId, ?string $errorMsg): void
    {
        try {
            DB::table('notification_logs')->insert([
                'user_id' => $userId,
                'fcm_token' => $token,
                'title' => $title,
                'body' => $body,
                'data' => json_encode($data),
                'status' => $status,
                'fcm_message_id' => $messageId,
                'error_message' => $errorMsg,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to insert notification_logs: " . $e->getMessage());
        }
    }
}

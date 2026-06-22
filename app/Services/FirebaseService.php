<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class FirebaseService
{
    /**
     * Resolve and parse the Firebase service account credentials.
     * Supports both a raw JSON string in env('FIREBASE_CREDENTIALS')
     * and a traditional local file path.
     *
     * @return array|null
     */
    private function getCredentials(): ?array
    {
        $credentialsSource = env('FIREBASE_CREDENTIALS');
        if (!$credentialsSource) {
            Log::warning("Firebase Credentials setting is not set in environment.");
            return null;
        }

        $credentials = null;
        if (str_starts_with(trim($credentialsSource), '{')) {
            $credentials = json_decode($credentialsSource, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Failed to parse raw JSON from FIREBASE_CREDENTIALS environment variable: " . json_last_error_msg());
                return null;
            }
        } else {
            if (!file_exists($credentialsSource)) {
                Log::warning("Firebase credentials file does not exist at path: {$credentialsSource}");
                return null;
            }
            $content = file_get_contents($credentialsSource);
            $credentials = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Failed to parse Firebase credentials JSON from file: " . json_last_error_msg());
                return null;
            }
        }

        if (!$credentials || !isset($credentials['client_email']) || !isset($credentials['private_key'])) {
            Log::error("Invalid Firebase credentials format: client_email or private_key is missing.");
            return null;
        }

        return $credentials;
    }

    /**
     * Get OAuth2 Bearer token for FCM v1 API using the Service Account JSON.
     * Generates a short-lived JWT and exchanges it for an access token.
     *
     * @return string|null
     */
    private function getAccessToken(): ?string
    {
        $credentials = $this->getCredentials();
        if (!$credentials) {
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
        $credentials = $this->getCredentials();
        $projectId = env('FIREBASE_PROJECT_ID');

        // Fallback to credentials project_id if env value is missing or placeholder
        if ((!$projectId || $projectId === 'your-firebase-project-id') && $credentials) {
            $projectId = $credentials['project_id'] ?? null;
        }

        // If credentials or project ID cannot be determined, fall back to mock simulated mode
        if (!$credentials || !$projectId || $projectId === 'your-firebase-project-id') {
            Log::info("FCM running in simulated mode. Credentials or project ID missing.");
            $this->logResult($userId, $token, $title, $body, $data, 'simulated', null, 'Credentials or project ID missing');
            return true;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            $this->logResult($userId, $token, $title, $body, $data, 'failed', null, 'Auth token failed');
            return false;
        }

        // Ensure all data values are strings for FCM v1 schema validation
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string)$key] = (string)$value;
        }

        // Dynamic sound/channel routing based on SOS message type
        $type = isset($stringData['type']) ? strtolower($stringData['type']) : '';
        $isSOS = ($type === 'sos' || $type === 'emergency_sos' || (isset($stringData['urgency']) && strtolower($stringData['urgency']) === 'emergency sos'));

        $androidChannel = $isSOS ? 'emergency-siren-channel' : 'jeevalink_urgent';
        $soundName = $isSOS ? 'siren' : 'default';
        $apnsSound = $isSOS ? 'siren.mp3' : 'default';

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
                        'channel_id' => $androidChannel,
                        'sound' => $soundName,
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => $apnsSound,
                        ]
                    ]
                ],
                'data' => (object)$stringData // ensure it's an object even if empty
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

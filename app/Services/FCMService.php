<?php

namespace App\Services;

class FCMService
{
    /**
     * Send push notification using Expo Push API or FCM.
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public static function sendPushNotification(string $token, string $title, string $body, array $data = []): bool
    {
        if (empty($token)) {
            return false;
        }

        // Detect Expo Push Token
        if (str_starts_with($token, 'ExponentPushToken') || str_contains($token, 'host.exp.exponent')) {
            return self::sendExpoNotification($token, $title, $body, $data);
        }

        return self::sendFCMNotification($token, $title, $body, $data);
    }

    /**
     * Send notification via Expo Push Notification service.
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    private static function sendExpoNotification(string $token, string $title, string $body, array $data): bool
    {
        $url = 'https://exp.host/--/api/v2/push/send';
        $payload = [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'priority' => 'high'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Accept-encoding: gzip, deflate'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Send notification via FCM Legacy API.
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    private static function sendFCMNotification(string $token, string $title, string $body, array $data): bool
    {
        $serverKey = env('FCM_SERVER_KEY', '');
        if (empty($serverKey) || $serverKey === 'mock_fcm_server_key') {
            // Log for development environment if FCM server key is not configured
            error_log("[FCM MOCK] Push notification to: {$token} | Title: {$title} | Body: {$body}");
            return true;
        }

        $url = 'https://fcm.googleapis.com/fcm/send';
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => $data,
            'priority' => 'high'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}

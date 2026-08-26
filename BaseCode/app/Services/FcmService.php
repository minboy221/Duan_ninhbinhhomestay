<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FcmService
{
    // tự động lấy Google Access token từ file storage/app/firebase.json
    public static function getAccessToken(&$errorMsg = null)
    {
        $cachedToken = Cache::get('firebase_access_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        $jsonPath = storage_path('app/firebase.json');
        if (!file_exists($jsonPath)) {
            $errorMsg = "Không tìm thấy tệp storage/app/firebase.json trên cPanel!";
            Log::error("FCM Error: " . $errorMsg);
            return null;
        }

        $credentials = json_decode(file_get_contents($jsonPath), true);
        if (empty($credentials['client_email']) || empty($credentials['private_key'])) {
            $errorMsg = "Tệp firebase.json bị rỗng hoặc thiếu client_email / private_key!";
            Log::error("FCM Error: " . $errorMsg);
            return null;
        }

        $clientEmail = $credentials['client_email'];
        $privateKey = str_replace('\n', "\n", $credentials['private_key']);

        // tạo JWT Token gửi sang google OAuth2 API
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $payload = json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;

        if (!openssl_sign($signatureInput, $signature, $privateKey, 'SHA256')) {
            $errorMsg = "Không thể tạo chữ ký openssl_sign từ private_key!";
            Log::error("FCM Error: " . $errorMsg);
            return null;
        }

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $signatureInput . "." . $base64UrlSignature;

        // xin Access Token từ Google
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        if ($response->failed()) {
            $errorMsg = "Google OAuth2 từ chối cấp Token: " . $response->body();
            Log::error("FCM OAuth2 Error: " . $errorMsg);
            return null;
        }

        $token = $response->json()['access_token'] ?? null;
        if ($token) {
            Cache::put('firebase_access_token', $token, 3500);
        } else {
            $errorMsg = "Google OAuth2 không trả về access_token!";
        }
        return $token;
    }

    // gửi push Notification tới điện thoại bằng Google FCM V1 API
    public static function sendPushNotification($fcmToken, string $title, string $body, string $url = '/', &$detailError = null)
    {
        if (!$fcmToken) {
            $detailError = 'fcm_token của người dùng bị trống.';
            Log::warning('FCM Push Skipped: fcm_token của user bị trống.');
            return false;
        }

        $accessToken = self::getAccessToken($detailError);
        if (!$accessToken) {
            Log::error('FCM Push Failed: ' . ($detailError ?? 'Không thể lấy Access Token từ Google.'));
            return false;
        }

        $jsonPath = storage_path('app/firebase.json');
        $projectId = 'datn-homestay-app';
        if (file_exists($jsonPath)) {
            $credentials = json_decode(file_get_contents($jsonPath), true);
            if (!empty($credentials['project_id'])) {
                $projectId = $credentials['project_id'];
            }
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'url' => (string) $url,
                ],
            ]
        ]);

        if ($response->failed()) {
            $detailError = 'Google FCM V1 API báo lỗi (' . $response->status() . '): ' . $response->body();
            Log::error('FCM Push V1 Failed: ' . $detailError);
            return false;
        }

        Log::info("FCM Push Sent Successfully to Token: " . substr($fcmToken, 0, 15) . "...");
        return true;
    }
}
?>
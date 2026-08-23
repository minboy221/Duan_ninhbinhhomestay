<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FcmService
{
    // tự động lấy Google Access token từ file storage/app/firebase.json
    private static function getAccessToken()
    {
        return Cache::remember('firebase_access_token', 3500, function () {
            $jsonPath = storage_Path('app/firebase.json');
            if (!file_exists($jsonPath)) {
                Log::error("FCM Error:Không tìm thấy file storage/app/firebase.json");
                return null;
            }
            $credentials = json_decode(file_get_contents($jsonPath), true);
            $clientEmail = $credentials['client_email'];
            $privateKey = $credentials['private_key'];
            //tạo JWT Token gửi sang google OAuth2 API
            $header = json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT'
            ]);
            $now = time();
            $payload = json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now
            ]);
            $base64UrlHeader = str_replace(['+','/','='],['-','_',''], base64_encode($header));
            $base64UrlPayload = str_replace(['+','/','='],['-','_',''], base64_encode($payload));
            $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;

            openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
            $base64UrlSignature = str_replace(['+','/','='],['-','_',''],
            base64_encode($signature));
            $jwt = $signatureInput . "." . $base64UrlSignature;
            //xin Access Token tiwf Google
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token',[
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);
            return $response->json()['access_token'] ?? null;
        });
    }
    //gửi push Notificaion với điện thoại bằng Google FCM V1 API
    public static function sendPushNotification($fcmToken, string $title, string $body, string $url = '/')
    {
        if(!$fcmToken) return false;
        $accessToken = self::getAccessToken();
        if(!$accessToken) return false;
        $projectId = env('VITE_FIREBASE_PROJECT_ID', 'datn-homestay-app');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",[
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'url' => $url,
                ],
            ]
        ]);
        if($response->failed()){
            Log::error('FCM Push V1 Failed: ' . $response->body());
        }
        return $response->successful();
    }
}
?>
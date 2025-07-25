<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class YandexController extends Controller
{
    public function redirectToProvider()
    {
        return Socialite::driver('yandex')
            ->scopes(['cloud_api:disk.read'])
            ->redirectUrl(route('yandex.callback'))
            ->redirect();
    }
    public function handleProviderCallback()
    {
        $yandexUser = Socialite::driver('yandex')->user();

        $user = User::updateOrCreate(
            ['email' => $yandexUser->getEmail() ?? 'yandex_'.$yandexUser->getId().'@placeholder.com'],
            [
                'name' => $yandexUser->getName() ?? 'Yandex User',
                'password' => bcrypt(Str::random(32)),
                'yandex_token' => $yandexUser->token,
                'yandex_refresh_token' => $yandexUser->refreshToken,
                'yandex_token_expires_at' => now()->addSeconds($yandexUser->expiresIn - 60),
            ]
        );

        Auth::login($user, true);
        return redirect()->intended('/dashboard');
    }
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:xlsx,xls'
        ]);
        $user = $request->user();
        if (!$user->yandex_token) {
            return response()->json([
                'success' => false,
                'message' => 'Yandex token not available. Please reauthenticate.'
            ], 401);
        }
        $tempFilePath = $this->storeTempFile($request->file('file'));
        $fileName = 'data.xlsx';
        $remotePath = '/Documents/' . $fileName;
        $result = $this->attemptUploadWithRetry($user, $tempFilePath, $remotePath);
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
        return $result;
    }

    public function generateApiKey(Request $request)
    {
        $user = $request->user();
        if ($user->api_key && $user->api_key_expires_at && $user->api_key_expires_at->isFuture()) {
            $nextAllowed = $user->api_key_expires_at->copy();
            return response()->json([
                'success' => false,
                'message' => 'API-ключ можно сгенерировать только один раз в месяц.',
                'next_allowed' => $nextAllowed->toDateTimeString(),
                'expires_at' => $user->api_key_expires_at->toDateTimeString(),
                'api_key' => $user->api_key,
            ], 429);
        }
        if (!$user->yandex_token) {
            return response()->json([
                'success' => false,
                'message' => 'Требуется авторизация через Яндекс.'
            ], 403);
        }
        $user->api_key = \Illuminate\Support\Str::random(64);
        $user->api_key_expires_at = now()->addMonth();
        $user->save();
        return response()->json([
            'success' => true,
            'api_key' => $user->api_key,
            'expires_at' => $user->api_key_expires_at->toDateTimeString(),
        ]);
    }

    private function attemptUploadWithRetry($user, $tempFilePath, $remotePath, $maxAttempts = 5, $delaySeconds = 10)
    {
        $attempt = 1;
        $lastError = null;

        while ($attempt <= $maxAttempts) {
            try {
                $this->ensureDirectory($user->yandex_token, '/Documents');

                $uploadUrl = $this->getUploadUrl($user->yandex_token, $remotePath);

                $uploadResponse = Http::withHeaders([
                    'Authorization' => 'OAuth ' . $user->yandex_token,
                    'Content-Type' => 'application/octet-stream',
                ])->withBody(
                    fopen($tempFilePath, 'r')
                )->put($uploadUrl);
                if ($uploadResponse->failed()) {
                    throw new \Exception('File upload failed: ' . $uploadResponse->body());
                }
                $publicUrl = $this->publishFile($user->yandex_token, $remotePath);
                return response()->json([
                    'success' => true,
                    'url' => $publicUrl,
                    'file_name' => basename($remotePath),
                    'action' => 'updated',
                    'attempts' => $attempt
                ]);
            } catch (\Exception $e) {
                $lastError = $e;
                Log::warning("Yandex upload attempt $attempt failed", [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'next_attempt_in' => "$delaySeconds seconds"
                ]);
                if ($attempt < $maxAttempts) {
                    sleep($delaySeconds);
                }
                $attempt++;
            }
        }

        Log::error('Yandex upload failed after all attempts', [
            'error' => $lastError->getMessage(),
            'user_id' => $user->id,
            'attempts' => $maxAttempts
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Upload failed after ' . $maxAttempts . ' attempts: ' . $lastError->getMessage()
        ], 500);
    }

    private function storeTempFile($file)
    {
        $tempPath = sys_get_temp_dir() . '/yandex_upload_' . uniqid() . '.xlsx';
        file_put_contents($tempPath, file_get_contents($file->getRealPath()));
        return $tempPath;
    }

    private function getFileInfo($token, $path)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'OAuth ' . $token,
                'Accept' => 'application/json',
            ])->get('https://cloud-api.yandex.net/v1/disk/resources', [
                'path' => $path,
                'fields' => 'revision'
            ]);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function ensureDirectory($token, $path)
    {
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->put('https://cloud-api.yandex.net/v1/disk/resources', [
                'headers' => [
                    'Authorization' => 'OAuth ' . $token,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'path' => $path
                ]
            ]);

            return true;
            
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 409) {
                return true;
            }
            throw new \Exception('Directory creation failed');
        }
    }

    private function getUploadUrl($token, $path)
    {
        $response = Http::withHeaders([
            'Authorization' => 'OAuth ' . $token,
            'Accept' => 'application/json',
        ])->get('https://cloud-api.yandex.net/v1/disk/resources/upload', [
            'path' => $path,
            'overwrite' => 'true'
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to get upload URL: ' . $response->body());
        }

        return $response->json('href');
    }

    private function publishFile($token, $path)
    {
        Http::withHeaders(['Authorization' => 'OAuth ' . $token])
            ->put('https://cloud-api.yandex.net/v1/disk/resources/publish', [
                'path' => $path
            ]);

        $meta = Http::withHeaders(['Authorization' => 'OAuth ' . $token])
                    ->get('https://cloud-api.yandex.net/v1/disk/resources', [
                        'path' => $path,
                        'fields' => 'public_url',
                    ]);
                    
        return $meta->json('public_url') ?? 'https://disk.yandex.ru/client/disk' . $path;
    }

}
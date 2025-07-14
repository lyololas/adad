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
            ->scopes(['cloud_api:disk.write'])
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

    try {
        $file = $request->file('file');
        $fileName = 'export_' . now()->format('Y-m-d_His') . '.xlsx';
        $remotePath = '/Documents/' . $fileName;

        // 1. Ensure directory exists
        $this->ensureDirectory($user->yandex_token, '/Documents');

        // 2. Get upload URL
        $uploadUrl = $this->getUploadUrl($user->yandex_token, $remotePath);

        // 3. Upload file
        $uploadResponse = Http::withHeaders([
            'Authorization' => 'OAuth ' . $user->yandex_token,
            'Content-Type' => 'application/octet-stream',
        ])->withBody(
            fopen($file->getRealPath(), 'r')
        )->put($uploadUrl);

        if ($uploadResponse->failed()) {
            throw new \Exception('File upload failed: ' . $uploadResponse->body());
        }

        // 4. Publish and get URL
        $publicUrl = $this->publishFile($user->yandex_token, $remotePath);

        return response()->json([
            'success' => true,
            'url' => $publicUrl,
            'file_name' => $fileName
        ]);

    } catch (\Exception $e) {
        Log::error('Yandex upload failed', [
            'error' => $e->getMessage(),
            'user_id' => $user->id,
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Upload failed: ' . $e->getMessage()
        ], 500);
    }
}

    /* ---------- helpers ---------- */

    private function ensureDirectory($token, $path)
{
    $client = new \GuzzleHttp\Client();
    
    try {
        $response = $client->put('https://cloud-api.yandex.net/v1/disk/resources', [
            'headers' => [
                'Authorization' => 'OAuth ' . $token,
                'Accept' => 'application/json',
            ],
            'query' => [ // This is the critical fix - use query instead of json
                'path' => $path
            ]
        ]);

        return true;
        
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $status = $e->getResponse()->getStatusCode();
        // 409 means directory already exists - that's fine
        if ($status === 409) {
            return true;
        }
        
        $responseBody = $e->getResponse()->getBody()->getContents();
        Log::error('Yandex directory creation failed', [
            'status' => $status,
            'response' => $responseBody,
            'path' => $path
        ]);
        throw new \Exception('Directory creation failed: ' . $responseBody);
    }
}
private function ensureDirectoryWithRetry($token, $path, $attempts = 3)
{
    $lastException = null;
    
    for ($i = 0; $i < $attempts; $i++) {
        try {
            return $this->ensureDirectory($token, $path);
        } catch (\Exception $e) {
            $lastException = $e;
            sleep(1); // Wait before retry
            continue;
        }
    }
    
    throw $lastException;
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

    Log::debug('Yandex upload URL response', [
        'status' => $response->status(),
        'response' => $response->json(),
        'path' => $path
    ]);

    if ($response->failed()) {
        throw new \Exception('Failed to get upload URL: ' . $response->body());
    }

    return $response->json('href');
}

private function publishFile($token, $path)
{
    Http::withHeaders(['Authorization' => 'OAuth ' . $token])
        ->put('https://cloud-api.yandex.net/v1/disk/resources/publish', ['path' => $path]);

    $meta = Http::withHeaders(['Authorization' => 'OAuth ' . $token])
                ->get('https://cloud-api.yandex.net/v1/disk/resources', [
                    'path'   => $path,
                    'fields' => 'public_url',
                ]);
    return $meta->json('public_url') ?? 'https://disk.yandex.ru/client/disk' . $path;
}
}
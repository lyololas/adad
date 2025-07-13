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
        Log::debug('Attempting Yandex redirect');
        
        try {
            return Socialite::driver('yandex')
                ->scopes(['cloud_api:disk.write']) // Add disk write permission
                ->redirectUrl(route('yandex.callback'))
                ->redirect();
            
        } catch (\Exception $e) {
            Log::error('YANDEX REDIRECT ERROR: ' . $e->getMessage());
            return redirect('/login')->withErrors('Yandex login unavailable');
        }
    }

    public function handleProviderCallback()
    {
        Log::debug('Yandex callback initiated');
        
        try {
            $yandexUser = Socialite::driver('yandex')->user();
            Log::debug('Yandex user data received', [
                'id' => $yandexUser->getId(),
                'email' => $yandexUser->getEmail(),
                'token' => $yandexUser->token
            ]);

            $email = $yandexUser->getEmail() ?? 'yandex_'.$yandexUser->getId().'@placeholder.com';
            
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $yandexUser->getName() ?? $yandexUser->getNickname() ?? 'Yandex User',
                    'password' => bcrypt(Str::random(32)),
                    'yandex_token' => $yandexUser->token, // Store the access token
                    'yandex_refresh_token' => $yandexUser->refreshToken,
                    'yandex_token_expires_at' => now()->addSeconds($yandexUser->expiresIn),
                ]
            );

            Auth::login($user, true);
            Log::debug('User logged in', ['user_id' => $user->id]);

            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            Log::error('YANDEX CALLBACK ERROR: ' . $e->getMessage());
            return redirect('/login')->withErrors('Failed to authenticate with Yandex');
        }
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        try {
            $user = $request->user();
            if (!$user || !$user->yandex_token) {
                throw new \Exception('No Yandex token available');
            }

            $file = $request->file('file');
            $fileName = 'export_' . now()->format('Y-m-d_H-i-s') . '_' . $file->getClientOriginalName();

            // Step 1: Get upload URL
            $response = Http::withHeaders([
                'Authorization' => 'OAuth ' . $user->yandex_token,
                'Accept' => 'application/json',
            ])->get('https://cloud-api.yandex.net/v1/disk/resources/upload', [
                'path' => '/Documents/' . $fileName,
                'overwrite' => 'true',
            ]);

            if ($response->failed()) {
                throw new \Exception('Failed to get upload URL: ' . $response->body());
            }

            $uploadData = $response->json();
            $uploadUrl = $uploadData['href'];

            // Step 2: Upload the file
            $uploadResponse = Http::withHeaders([
                'Authorization' => 'OAuth ' . $user->yandex_token,
            ])->put($uploadUrl, file_get_contents($file->getRealPath()));

            if ($uploadResponse->failed()) {
                throw new \Exception('Upload failed: ' . $uploadResponse->body());
            }

            // Step 3: Get public view URL
            $publishResponse = Http::withHeaders([
                'Authorization' => 'OAuth ' . $user->yandex_token,
            ])->put('https://cloud-api.yandex.net/v1/disk/resources/publish', [
                'path' => '/Documents/' . $fileName,
            ]);

            if ($publishResponse->failed()) {
                throw new \Exception('Publish failed: ' . $publishResponse->body());
            }

            $metaResponse = Http::withHeaders([
                'Authorization' => 'OAuth ' . $user->yandex_token,
            ])->get('https://cloud-api.yandex.net/v1/disk/resources', [
                'path' => '/Documents/' . $fileName,
            ]);

            if ($metaResponse->failed()) {
                throw new \Exception('Failed to get file metadata: ' . $metaResponse->body());
            }

            $fileMeta = $metaResponse->json();
            $publicUrl = $fileMeta['public_url'] ?? 'https://disk.yandex.ru/client/disk/Documents/' . rawurlencode($fileName);

            return response()->json([
                'success' => true,
                'url' => $publicUrl,
                'file_name' => $fileName,
            ]);

        } catch (\Exception $e) {
            Log::error('Yandex Disk upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'File upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refreshToken(User $user)
    {
        try {
            $response = Http::asForm()->post('https://oauth.yandex.com/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $user->yandex_refresh_token,
                'client_id' => config('services.yandex.client_id'),
                'client_secret' => config('services.yandex.client_secret'),
            ]);

            $data = $response->json();

            $user->update([
                'yandex_token' => $data['access_token'],
                'yandex_refresh_token' => $data['refresh_token'],
                'yandex_token_expires_at' => now()->addSeconds($data['expires_in']),
            ]);

            return $data['access_token'];

        } catch (\Exception $e) {
            Log::error('Yandex token refresh failed: ' . $e->getMessage());
            return null;
        }
    }
}
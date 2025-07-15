<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;

class ApiKeySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereNull('api_key')->get();
        foreach ($users as $user) {
            $user->api_key = Str::random(64);
            $user->api_key_expires_at = now()->addMonth();
            $user->save();
            echo "{$user->email}: {$user->api_key}\n";
        }
    }
} 
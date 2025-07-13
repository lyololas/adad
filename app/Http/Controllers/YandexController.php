<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
class YandexController extends Controller
{
    public function redirectToProvider()
    {
        return Socialite::driver('yandex')->redirect();
    }

    public function handleProviderCallback()
    {
        $yandexUser = Socialite::driver('yandex')->user();

        $user = User::firstOrCreate(
            ['email' => $yandexUser->getEmail()],
            [
                'name' => $yandexUser->getName() ?? $yandexUser->getNickname(),
            ]
        );

        Auth::login($user, true);

        return redirect()->intended('/');
    }
}

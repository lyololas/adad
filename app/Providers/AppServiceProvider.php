<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use SocialiteProviders\Manager\SocialiteWasCalled;
use App\Http\Middleware\ApiKeyAuth;
use App\Http\Middleware\CheckYandexToken;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('yandex', \SocialiteProviders\Yandex\Provider::class);
        });
        Route::aliasMiddleware('api.key', ApiKeyAuth::class);
        Route::aliasMiddleware('check.yandex.token', CheckYandexToken::class);
    }
}
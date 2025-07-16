<?php
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\YandexController;


Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/my-organization', [RegisteredUserController::class, 'show'])
     ->middleware('auth')
     ->name('myorg');
Route::get('/tilda-exporter', [RegisteredUserController::class, 'show'])
     ->middleware('auth')
     ->name('tilda');
Route::get('/request-form', function () {
    return Inertia::render('auth/RequestForm');
})->name('request.form');
Route::get('/settings/api-key', function () {
    return Inertia::render('settings/ApiKey');
})->middleware(['auth', 'check.yandex.token']);

Route::get('/auth/yandex', [YandexController::class, 'redirectToProvider'])->name('yandex.login');
Route::get('/auth/yandex/callback', [YandexController::class, 'handleProviderCallback'])->name('yandex.callback');
Route::post('/upload-to-yandex', [YandexController::class, 'upload'])
     ->middleware(['web', 'auth']);  
Route::post('/generate-api-key', [YandexController::class, 'generateApiKey'])
     ->middleware(['auth', 'check.yandex.token']);
Route::get('/consent-form', function () {
    return Inertia::render('auth/ConsentForm');
})->name('consent.form');
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


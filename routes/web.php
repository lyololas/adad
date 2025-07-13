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

// routes/web.php
Route::get('/auth/yandex', [YandexController::class, 'redirectToProvider'])->name('yandex.login');
Route::get('/auth/yandex/callback', [YandexController::class, 'handleProviderCallback'])->name('yandex.callback');
Route::post('/upload-to-yandex', [YandexController::class, 'upload']);
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


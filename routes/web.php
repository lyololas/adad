<?php
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/my-organization', [RegisteredUserController::class, 'show'])
     ->middleware('auth')
     ->name('myorg');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

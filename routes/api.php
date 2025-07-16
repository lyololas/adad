<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\YandexApiController;

Route::post('/upload', [YandexApiController::class, 'upload']); 
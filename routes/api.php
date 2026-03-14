<?php

use App\Http\Controllers\RedirectController;
use App\Http\Controllers\ShortUrlController;
use Illuminate\Support\Facades\Route;

Route::controller(ShortUrlController::class)->group(function () {
    Route::post('/short-urls', 'create');
    Route::get('/short-urls/{code}', 'findByCode');
});

Route::controller(RedirectController::class)->group(function () {
    Route::get('/{code}', '__invoke');
});

<?php

use App\Http\Controllers\ShortUrlController;
use Illuminate\Support\Facades\Route;

Route::controller(ShortUrlController::class)->group(function () {
    Route::post('/short-urls', 'create');
    Route::get('/short-urls/{code}', 'findByCode');
    Route::get('/{code}', 'redirect');
});

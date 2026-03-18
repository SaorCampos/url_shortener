<?php

use App\Http\Controllers\ShortUrlController;
use Illuminate\Support\Facades\Route;

Route::controller(ShortUrlController::class)->middleware('api')->group(function () {
    Route::post('/short-urls', 'create')->middleware('throttle:10,1');
    Route::get('/short-urls/{code}', 'findByCode');
});

<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ShortUrlController;
use Illuminate\Support\Facades\Route;

Route::controller(ShortUrlController::class)->middleware('api')->group(function () {
    Route::post('/short-urls', 'create')->middleware('throttle:10,1');
    Route::get('/short-urls/{code}', 'findByCode');
});

Route::controller(AnalyticsController::class)->middleware('api')->group(function () {
    Route::get('/analytics/{code}', 'analytics');
    Route::get('/analytics-top-day', 'top');
    Route::get('/analytics-top-hour', 'topLastHour');
    Route::get('/analytics-countries/{code}', 'countries');
    Route::get('/analytics-heatmap/{code}', 'heatmap');
    Route::get('/analytics-geo/{code}', 'geoHeatmap');
});

<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::controller(RedirectController::class)->group(function () {
    Route::get('/{code}', '__invoke')->where('code', '[A-Za-z0-9]{6}');
});

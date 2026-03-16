<?php

use App\Console\Commands\ProcessClickStream;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('shorturl:process-clicks', function () {
    $this->call(ProcessClickStream::class);
});

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Finance: Cache hot accounts hourly for fast balance retrieval
Schedule::command('finance:cache-hot-accounts')
    ->hourly()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/hot-accounts-cache.log'));

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

use Illuminate\Support\Facades\Schedule;
use App\Models\EmergencyRequest;

Schedule::call(function () {
    EmergencyRequest::where('status', 'pending')
        ->where('expires_at', '<=', now())
        ->update(['status' => 'expired']);
})->everyFiveMinutes();

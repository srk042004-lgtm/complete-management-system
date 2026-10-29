<?php

use App\Support\TrialLifecycle;
use Illuminate\Support\Facades\Schedule;

Artisan::command('trials:cancel-lapsed', function () {
    $count = TrialLifecycle::cancelLapsedTrials();
    $this->info("Cancelled {$count} hotel(s) whose trial ended without a subscription.");
})->purpose('Suspend hotels whose free trial ended without a paid subscription');

Schedule::command('trials:cancel-lapsed')->hourly()->withoutOverlapping();

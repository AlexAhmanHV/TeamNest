<?php

use App\Console\Commands\SendInvitationRemindersCommand;
use App\Console\Commands\SendTaskDueRemindersCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendInvitationRemindersCommand::class)->daily();
Schedule::command(SendTaskDueRemindersCommand::class)->dailyAt('08:00');

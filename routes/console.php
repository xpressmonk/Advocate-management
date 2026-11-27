<?php
use Illuminate\Support\Facades\Schedule;

Schedule::command('hearings:send-reminders')->dailyAt('08:00');
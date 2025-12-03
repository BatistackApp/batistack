<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('chantiers:check-delay')
    ->dailyAt('00:00')
    ->description("Vérifie les chantiers dont la date de fin prévue est dépassée");

Schedule::command('inventory:check-alert')
    ->dailyAt('08:00')
    ->description("Vérifie les stocks inférieurs au seuil d'alerte");

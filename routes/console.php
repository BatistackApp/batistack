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

Schedule::command('bank:sync')
    ->twiceDaily(6,13)
    ->description("Synchronise les comptes bancaires BridgeAPI");

Schedule::command("rh:check-timesheets")
    ->weekdays()
    ->at('09:00')
    ->description("Vérifie les pointages manquants de la veille");

Schedule::command('expenses:remind')
    ->monthlyOn(25, '10:00')
    ->description("Rappel aux employés de soumettre leurs notes de frais");

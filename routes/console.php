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

Schedule::command('ged:check-expirations')
    ->dailyAt('08:00')
    ->description("Vérifie les documents qui expirent bientôt (Assurances, Contrats)");

Schedule::command('payroll:create-period')
    ->monthlyOn(1, '00:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/schedule/payroll-create-period.log'));

Schedule::command('fleet:check-expirations')
    ->dailyAt('08:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/schedule/fleet-check-expirations.log'))
    ->description("Vérifie les dates d'expiration des assurances de flotte et envoie des alertes.");

Schedule::command("fleet:sync-ulys-consumptions")
    ->daily()
    ->onOneServer()
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/schedule/sync-ulys-consumptions.log'))
    ->description("Synchronise les consommations de télépéage Ulys pour les flottes configurées.");

Schedule::command('fleet:check-maintenance-alerts')
    ->daily()
    ->onOneServer()
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/schedule/fleet-check-maintenance-alerts.log'))
    ->description("Synchronise les maintenance pour les flottes");

Schedule::command('fleet:check-assignment-reminders')
    ->dailyAt('09:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/schedule/fleet-check-assignment-reminders.log'))
    ->description("Vérifie les assignations de flotte dont la fin approche et envoie des rappels.");

Schedule::command("compta:check-integrity")
    ->daily()
    ->onOneServer()
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/schedule/compta-check-integrity.log'))
    ->description("Vérifie que la comptabilité est équilibrée (Débit = Crédit) pour chaque entreprise.");

Schedule::command('compta:generate-reports')
    ->monthlyOn(1, '03:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/schedule/compta-generate-reports.log'))
    ->description("Génère le rapport comptable pour toutes les companie.");

Schedule::job(new \App\Jobs\Comptabilite\PostRecurringEntriesJob())
    ->dailyAt('04:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/schedule/comptabilite-post-recurring-entries.log'));

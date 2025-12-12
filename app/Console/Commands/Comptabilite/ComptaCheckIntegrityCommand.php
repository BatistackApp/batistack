<?php

namespace App\Console\Commands\Comptabilite;

use App\Models\Comptabilite\ComptaEntry;
use App\Models\Core\Company;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Console\Command;

class ComptaCheckIntegrityCommand extends Command
{
    protected $signature = 'compta:check-integrity';

    protected $description = 'Vérifie que la comptabilité est équilibrée (Débit = Crédit) pour chaque entreprise.';

    public function handle(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $balance = ComptaEntry::where('company_id', $company->id)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();
            $diff = abs($balance->total_debit - $balance->total_credit);

            if ($diff > 0.01) { // Tolérance de 1 centime pour les arrondis
                $this->error("ALERTE : Déséquilibre comptable pour la société {$company->name} (Diff: {$diff} €)");
                // Ici : Envoyer une notification Slack/Email aux Admins Système
                $notifiables = User::where('role', 'admin')->get();

                Notification::make()
                    ->title("Déséquilibre comptable")
                    ->body("Certaines ecritures comptables sont déséquilibré, la clôture n'est donc pas disponible")
                    ->warning()
                    ->icon(Heroicon::ExclamationTriangle)
                    ->sendToDatabase($notifiables);
            } else {
                $this->info("OK : Société {$company->name} équilibrée.");
            }
        }
    }
}

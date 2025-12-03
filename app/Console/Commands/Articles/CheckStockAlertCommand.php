<?php

namespace App\Console\Commands\Articles;

use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckStockAlertCommand extends Command
{
    protected $signature = 'inventory:check-alert';

    protected $description = 'Vérifie les stocks inférieurs au seuil d\'alerte';

    public function handle(): void
    {
        // On cherche les stocks où Quantité Réelle <= Seuil ET Seuil > 0
        $lowStocks = InventoryStock::query()
            ->with(['product', 'warehouse', 'company'])
            ->whereNotNull('alert_threshold')
            ->whereColumn('quantity_on_hand', '<=', 'alert_threshold')
            ->get();

        if ($lowStocks->isEmpty()) {
            $this->info('Aucun stock en alerte.');
            return;
        }

        // Grouper par Company pour envoyer un seul mail récapitulatif par client
        $grouped = $lowStocks->groupBy('company_id');

        foreach ($grouped as $companyId => $stocks) {
            $company = $stocks->first()->company;

            // Logique : Trouver les admins de cette company
            $admins = $company->users()->where('is_admin', true)->get();

            $this->info("Alerte pour l'entreprise {$company->name} : " . $stocks->count() . " articles.");

            // Ici, tu déclencherais l'envoi de mail/notification
            // Notification::send($admins, new StockAlertNotification($stocks));
            Notification::make()
                ->warning()
                ->title("Stock en alerte: ".$stocks->count()." ".Str::plural('article', $stocks->count()). " en alerte de stock")
                ->sendToDatabase($admins);
        }
    }
}

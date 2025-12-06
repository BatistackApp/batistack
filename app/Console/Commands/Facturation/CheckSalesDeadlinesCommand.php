<?php

namespace App\Console\Commands\Facturation;

use App\Enums\Facturation\SalesDocumentStatus;
use App\Enums\Facturation\SalesDocumentType;
use App\Models\Facturation\SalesDocument;
use Illuminate\Console\Command;

class CheckSalesDeadlinesCommand extends Command
{
    protected $signature = 'sales:check-deadlines';

    protected $description = 'Vérifie les échéances des devis et factures';

    public function handle(): void
    {
        $today = now()->startOfDay();

        // 1. Gérer les Devis Expirés
        // On cherche les devis "Envoyés" dont la date de validité est passée
        $expiredQuotes = SalesDocument::query()
            ->where('type', SalesDocumentType::Quote)
            ->where('status', SalesDocumentStatus::Sent)
            ->where('validity_date', '<', $today)
            ->update(['status' => SalesDocumentStatus::Refused]); // Ou un statut 'Expired' si tu l'ajouté à l'Enum

        if ($expiredQuotes > 0) {
            $this->info("{$expiredQuotes} devis marqués comme expirés.");
        }

        // 2. Gérer les Factures en Retard
        // On cherche les factures "Envoyées" ou "Partielles" dont l'échéance est passée
        // ET qui ne sont pas déjà marquées "Overdue" (pour éviter de boucler)
        $overdueInvoices = SalesDocument::query()
            ->where('type', SalesDocumentType::Invoice)
            ->whereIn('status', [SalesDocumentStatus::Sent, SalesDocumentStatus::Partial])
            ->where('due_date', '<', $today)
            ->get(); // On fait un get() car on veut déclencher l'Observer pour chaque update

        foreach ($overdueInvoices as $invoice) {
            // Le changement de statut ici va déclencher l'Observer (voir point 3)
            $invoice->update(['status' => SalesDocumentStatus::Overdue]);
        }

        $this->info($overdueInvoices->count() . " factures passées en retard.");

    }
}

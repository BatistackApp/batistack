<?php

namespace App\Observers\Facturation;

use App\Enums\Facturation\SalesDocumentStatus;
use App\Enums\Facturation\SalesDocumentType;
use App\Models\Facturation\SalesDocument;
use App\Notifications\Facturation\InvoiceOverdueNotification;
use App\Services\Comptabilite\SalesDocumentComptaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SalesDocumentObserver
{
    public function creating(SalesDocument $document): void
    {
        // 1. Valeurs par défaut
        if (!$document->currency_code) {
            $document->currency_code = 'EUR';
        }

        // Date de validité par défaut (ex: 1 mois pour un devis)
        if ($document->type === SalesDocumentType::Quote && !$document->validity_date) {
            $document->validity_date = now()->addMonth();
        }

        // 2. Génération du Numéro (Critique)
        // Format : FAC-2024-0001 ou DEV-2024-0001

        if (empty($document->reference)) {
            $prefix = match($document->type) {
                SalesDocumentType::Quote => 'DEV',
                SalesDocumentType::Invoice => 'FAC',
                SalesDocumentType::CreditNote => 'AVR',
                SalesDocumentType::Deposit => 'ACP',
                default => 'DOC',
            };

            $year = now()->format('Y');

            // On cherche le dernier numéro pour ce TYPE et cette COMPANY cette ANNÉE
            // Note: Pour une facturation à fort trafic, on utiliserait un verrou (Lock) ici.
            $lastDoc = SalesDocument::where('company_id', $document->company_id)
                ->where('type', $document->type)
                ->whereYear('date', $year)
                ->orderByDesc('id') // On se fie à l'ID ou une colonne 'sequence' dédiée
                ->first();

            // On extrait la séquence (ex: 0001)
            $sequence = 1;
            if ($lastDoc && preg_match('/-(\d+)$/', $lastDoc->reference, $matches)) {
                $sequence = intval($matches[1]) + 1;
            }

            $document->reference = sprintf('%s-%s-%04d', $prefix, $year, $sequence);
        }
    }

    public function updated(SalesDocument $document): void
    {
        // Si le statut vient de changer pour "Overdue"
        if ($document->isDirty('status') && $document->status === SalesDocumentStatus::Overdue) {

            // On récupère les admins de l'entreprise concernée
            // (Supposons que tu as un scope 'admins' sur ton User model, ou tu prends tout le monde)
            $admins = $document->company->users;

            Notification::send($admins, new InvoiceOverdueNotification($document));
        }
    }

    /**
     * Handle the SalesDocument "saved" event.
     * This event is fired after a model is created or updated.
     */
    public function saved(SalesDocument $document): void
    {
        // Comptabilisation des factures
        if ($document->type === SalesDocumentType::Invoice && $document->isDirty('status')) {
            $originalStatus = $document->getOriginal('status');
            $newStatus = $document->status;

            // Si la facture passe de n'importe quel statut à "Envoyé" ou "Payé"
            // et qu'elle n'a pas déjà été comptabilisée (le service gère la vérification)
            if (in_array($newStatus, [SalesDocumentStatus::Sent, SalesDocumentStatus::Paid])) {
                try {
                    $comptaService = new SalesDocumentComptaService();
                    $comptaService->postSalesDocumentEntry($document);
                    Log::info("Facture {$document->reference} comptabilisée avec succès.");
                } catch (\Exception $e) {
                    Log::error("Erreur lors de la comptabilisation de la facture {$document->reference}: " . $e->getMessage());
                    // Optionnel: Notifier l'administrateur ou l'utilisateur de l'échec
                }
            }
        }
    }
}

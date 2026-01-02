<?php

namespace App\Observers\Facturation;

use App\Enums\Articles\ProductType;
use App\Enums\Facturation\SalesDocumentStatus;
use App\Enums\Facturation\SalesDocumentType;
use App\Enums\GPAO\ProductionOrderStatus;
use App\Models\Facturation\SalesDocument;
use App\Models\GPAO\ProductionOrder;
use App\Notifications\Facturation\InvoiceOverdueNotification;
use App\Services\Comptabilite\SalesDocumentComptaService;
use App\Services\GPAO\ProductionPlanningService;
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

    public function created(SalesDocument $document): void
    {
        if ($document->type === SalesDocumentType::Invoice && $document->chantiers_id) {
            $document->chantiers->increment('total_sales_revenue', $document->total_ht);
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

        // Si le coût a changé
        if ($document->type === SalesDocumentType::Invoice && $document->isDirty('total_ht')) {
            $oldCost = $document->getOriginal('total_ht');
            $newCost = $document->total_ht;
            if ($document->chantiers_id) {
                $document->chantiers->increment('total_sales_revenue', $newCost - $oldCost);
            }
        }

        // Si le chantier a changé
        if ($document->type === SalesDocumentType::Invoice && $document->isDirty('chantiers_id')) {
            // Retirer l'ancien coût de l'ancien chantier
            if ($document->getOriginal('chantiers_id')) {
                $oldChantier = \App\Models\Chantiers\Chantiers::find($document->getOriginal('chantiers_id'));
                if ($oldChantier) {
                    $oldChantier->decrement('total_sales_revenue', $document->getOriginal('total_ht'));
                }
            }
            // Ajouter le nouveau coût au nouveau chantier
            if ($document->chantiers_id) {
                $document->chantiers->increment('total_sales_revenue', $document->total_ht);
            }
        }
    }

    public function deleted(SalesDocument $document): void
    {
        if ($document->type === SalesDocumentType::Invoice && $document->chantiers_id) {
            $document->chantiers->decrement('total_sales_revenue', $document->total_ht);
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

        // Création des Ordres de Fabrication à partir d'un devis accepté
        if ($document->type === SalesDocumentType::Quote && $document->isDirty('status') && $document->status === SalesDocumentStatus::Accepted) {
            $this->createProductionOrdersFromQuote($document);
        }
    }

    /**
     * Crée les ordres de fabrication nécessaires à partir d'un devis accepté.
     */
    private function createProductionOrdersFromQuote(SalesDocument $quote): void
    {
        $planningService = new ProductionPlanningService();

        foreach ($quote->lines as $line) {
            // On ne traite que les produits de type "Ouvrage" (Assembly)
            if ($line->product->type === ProductType::Assembly) {
                $product = $line->product;
                $quantityToProduce = $line->quantity;
                $currentStock = $product->total_stock;

                // Si le stock est insuffisant
                if ($currentStock < $quantityToProduce) {
                    $missingQuantity = $quantityToProduce - $currentStock;

                    // Créer un Ordre de Fabrication pour la quantité manquante
                    $order = ProductionOrder::create([
                        'company_id' => $quote->company_id,
                        'sales_document_line_id' => $line->id,
                        'product_id' => $product->id,
                        'quantity' => $missingQuantity,
                        'status' => ProductionOrderStatus::Draft, // On crée en brouillon d'abord
                        'notes' => "Généré automatiquement à partir du devis {$quote->reference}",
                    ]);

                    // Planification automatique
                    $planningService->schedule($order);

                    Log::info("OF créé et planifié pour {$missingQuantity} de {$product->name} à partir du devis {$quote->reference}.");
                }
            }
        }
    }
}

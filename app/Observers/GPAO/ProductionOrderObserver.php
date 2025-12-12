<?php

namespace App\Observers\GPAO;

use App\Enums\GPAO\ProductionOrderStatus;
use App\Models\GPAO\ProductionOrder;
use App\Models\Articles\InventoryStock;
use App\Models\Articles\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductionOrderObserver
{
    /**
     * Handle the ProductionOrder "saved" event.
     * This event is fired after a model is created or updated.
     */
    public function saved(ProductionOrder $productionOrder): void
    {
        // Vérifier si le statut a changé pour "Completed"
        if ($productionOrder->isDirty('status') && $productionOrder->status === ProductionOrderStatus::Completed) {
            try {
                DB::transaction(function () use ($productionOrder) {
                    $productToProduce = $productionOrder->product;
                    $quantityProduced = $productionOrder->quantity;

                    // 1. Décrémenter les stocks des composants
                    foreach ($productToProduce->children as $component) {
                        $requiredQuantity = $component->pivot->quantity * $quantityProduced;

                        // Trouver un stock pour le composant (simplifié : prend le premier trouvé ou le stock par défaut)
                        $stock = InventoryStock::where('product_id', $component->id)
                            ->where('company_id', $productionOrder->company_id)
                            ->first(); // TODO: Gérer le choix du dépôt de manière plus sophistiquée

                        if (!$stock) {
                            throw new Exception("Stock introuvable pour le composant {$component->name} (ID: {$component->id}).");
                        }

                        if ($stock->quantity_on_hand < $requiredQuantity) {
                            throw new Exception("Stock insuffisant pour le composant {$component->name}. Nécessaire: {$requiredQuantity}, Disponible: {$stock->quantity_on_hand}.");
                        }

                        $stock->decrement('quantity_on_hand', $requiredQuantity);
                        Log::info("OF {$productionOrder->reference}: Décrémenté {$requiredQuantity} de {$component->name}. Stock restant: {$stock->quantity_on_hand}.");
                    }

                    // 2. Incrémenter le stock du produit fini
                    $finishedProductStock = InventoryStock::where('product_id', $productToProduce->id)
                        ->where('company_id', $productionOrder->company_id)
                        ->first(); // TODO: Gérer le choix du dépôt de manière plus sophistiquée

                    if (!$finishedProductStock) {
                        // Si pas de stock existant, le créer (ou lever une exception selon la politique)
                        $finishedProductStock = InventoryStock::create([
                            'product_id' => $productToProduce->id,
                            'company_id' => $productionOrder->company_id,
                            'warehouse_id' => 1, // TODO: Définir un dépôt par défaut ou le choisir
                            'quantity_on_hand' => 0,
                            'min_stock_level' => 0,
                        ]);
                    }

                    $finishedProductStock->increment('quantity_on_hand', $quantityProduced);
                    Log::info("OF {$productionOrder->reference}: Incrémenté {$quantityProduced} de {$productToProduce->name}. Stock total: {$finishedProductStock->quantity_on_hand}.");
                });

                Log::info("Ordre de Fabrication {$productionOrder->reference} traité avec succès. Stocks mis à jour.");

            } catch (Exception $e) {
                Log::error("Erreur lors du traitement de l'OF {$productionOrder->reference}: " . $e->getMessage());
                // Optionnel : Rejeter la transaction ou marquer l'OF avec un statut d'erreur
                // throw $e; // Rejeter la transaction si l'erreur est critique
            }
        }
    }

    /**
     * Handle the ProductionOrder "deleted" event.
     */
    public function deleted(ProductionOrder $productionOrder): void
    {
        // Optionnel : Gérer l'annulation d'un OF terminé (remettre les stocks ?)
        // C'est une logique complexe qui dépend des règles métier (ex: annulation possible si OF récent, sinon OD de correction)
    }
}

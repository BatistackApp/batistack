<?php

namespace App\Observers\GPAO;

use App\Enums\GPAO\ProductionOrderStatus;
use App\Models\GPAO\ProductionOrder;
use App\Models\Articles\InventoryStock; // Assuming InventoryStock model exists
use App\Models\Articles\ProductAssembly; // Assuming ProductAssembly model exists for nomenclature
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionOrderObserver
{
    /**
     * Handle the ProductionOrder "saved" event.
     */
    public function saved(ProductionOrder $productionOrder): void
    {
        // Vérifier si le statut est passé à 'Completed'
        if ($productionOrder->isDirty('status') && $productionOrder->status === ProductionOrderStatus::Completed) {
            $this->updateStockForCompletedOrder($productionOrder);
        }
    }

    /**
     * Met à jour les stocks pour un ordre de fabrication complété.
     * Décrémente les composants et incrémente le produit fini.
     */
    protected function updateStockForCompletedOrder(ProductionOrder $productionOrder): void
    {
        DB::transaction(function () use ($productionOrder) {
            $productToProduce = $productionOrder->product;
            $quantityProduced = $productionOrder->quantity;
            $companyId = $productionOrder->company_id;

            // 1. Décrémenter les stocks des composants
            // On suppose que la nomenclature est définie via ProductAssembly
            $assemblies = ProductAssembly::where('product_id', $productToProduce->id)->get();

            if ($assemblies->isEmpty()) {
                Log::warning("L'ordre de fabrication {$productionOrder->reference} est complété, mais aucune nomenclature trouvée pour le produit {$productToProduce->name}. Stocks non décrémentés.");
            }

            foreach ($assemblies as $assembly) {
                $component = $assembly->component; // Le composant est un autre produit
                $requiredQuantity = $assembly->quantity * $quantityProduced;

                // Trouver le stock du composant pour la compagnie
                $inventoryStock = InventoryStock::firstOrNew([
                    'company_id' => $companyId,
                    'product_id' => $component->id,
                    // Idéalement, on devrait spécifier un entrepôt ici
                ]);

                if ($inventoryStock->exists && $inventoryStock->quantity >= $requiredQuantity) {
                    $inventoryStock->quantity -= $requiredQuantity;
                    $inventoryStock->save();
                    Log::info("Décrémentation du stock du composant {$component->name} de {$requiredQuantity} pour l'OF {$productionOrder->reference}.");
                } else {
                    // Gérer le cas où le stock est insuffisant ou n'existe pas
                    Log::error("Stock insuffisant ou inexistant pour le composant {$component->name} ({$inventoryStock->quantity} disponibles, {$requiredQuantity} requis) pour l'OF {$productionOrder->reference}.");
                    // Optionnel: throw new \Exception("Stock insuffisant pour le composant {$component->name}.");
                }
            }

            // 2. Incrémenter le stock du produit fini
            $finishedProductStock = InventoryStock::firstOrNew([
                'company_id' => $companyId,
                'product_id' => $productToProduce->id,
                // Idéalement, on devrait spécifier un entrepôt ici
            ]);

            $finishedProductStock->quantity += $quantityProduced;
            $finishedProductStock->save();
            Log::info("Incrémentation du stock du produit fini {$productToProduce->name} de {$quantityProduced} pour l'OF {$productionOrder->reference}.");
        });
    }
}

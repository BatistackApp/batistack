<?php

namespace App\Services\GPAO;

use App\Enums\GPAO\ProductionOrderStatus;
use App\Models\Articles\InventoryStock;
use App\Models\Articles\ProductAssembly;
use App\Models\Core\Company;
use App\Models\GPAO\ProductionOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MaterialRequirementService
{
    /**
     * Calcule les déficits en matériaux pour les ordres de fabrication planifiés ou en cours.
     *
     * @param Company $company L'entreprise pour laquelle calculer les besoins.
     * @param Carbon|null $planningEndDate Date de fin de la période de planification (optionnel).
     * @return Collection Une collection de déficits [product_id => deficit_quantity].
     */
    public function calculateDeficits(Company $company, ?Carbon $planningEndDate = null): Collection
    {
        $rawMaterialNeeds = collect(); // [product_id => total_needed_quantity]

        // 1. Récupérer les ordres de fabrication planifiés ou en cours
        $productionOrders = ProductionOrder::where('company_id', $company->id)
            ->whereIn('status', [ProductionOrderStatus::Planned, ProductionOrderStatus::InProgress])
            ->when($planningEndDate, fn ($query) => $query->where('planned_end_date', '<=', $planningEndDate))
            ->with('product') // Charger le produit fini
            ->get();

        // 2. Calculer les besoins bruts en matériaux pour tous les OF
        foreach ($productionOrders as $productionOrder) {
            // Trouver la nomenclature (recette) pour le produit fini de cet OF
            $assemblies = ProductAssembly::where('parent_product_id', $productionOrder->product_id)->get();

            foreach ($assemblies as $assembly) {
                $componentId = $assembly->child_product_id;
                $quantityPerProduct = $assembly->quantity; // Quantité de composant par unité de produit fini

                // Besoin total de ce composant pour cet OF
                $totalNeededForThisOF = $quantityPerProduct * $productionOrder->quantity;

                // Agrégation des besoins bruts
                $rawMaterialNeeds->put(
                    $componentId,
                    $rawMaterialNeeds->get($componentId, 0) + $totalNeededForThisOF
                );
            }
        }

        // 3. Récupérer le stock disponible pour tous les composants identifiés
        $componentIds = $rawMaterialNeeds->keys();
        $availableStocks = InventoryStock::where('company_id', $company->id)
            ->whereIn('product_id', $componentIds)
            ->get()
            ->groupBy('product_id')
            ->map(fn ($stocks) => $stocks->sum('quantity_on_hand') - $stocks->sum('quantity_reserved')); // Stock disponible = En main - Réservé

        // 4. Calculer les déficits (Besoin Net)
        $deficits = collect();
        foreach ($rawMaterialNeeds as $componentId => $neededQuantity) {
            $availableQuantity = $availableStocks->get($componentId, 0);

            if ($neededQuantity > $availableQuantity) {
                $deficits->put($componentId, $neededQuantity - $availableQuantity);
            }
        }

        return $deficits;
    }
}

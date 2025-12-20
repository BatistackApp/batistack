<?php

namespace App\Observers\Interventions;

use App\Models\Articles\InventoryStock;
use App\Models\Articles\Warehouse;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionProduct;
use Exception;
use Illuminate\Validation\ValidationException;

class InterventionProductObserver
{
    /**
     * Handle the InterventionProduct "created" event.
     */
    public function created(InterventionProduct $pivot): void
    {
        $this->updateStock($pivot, 'decrement');
        $this->recalculateMaterialCost($pivot->intervention_id);
    }

    /**
     * Handle the InterventionProduct "updated" event.
     */
    public function updated(InterventionProduct $pivot): void
    {
        $originalQuantity = $pivot->getOriginal('quantity');
        $newQuantity = $pivot->quantity;
        $diff = $newQuantity - $originalQuantity;

        if ($diff > 0) {
            $this->updateStock($pivot, 'decrement', $diff);
        } elseif ($diff < 0) {
            $this->updateStock($pivot, 'increment', abs($diff));
        }

        $this->recalculateMaterialCost($pivot->intervention_id);
    }

    /**
     * Handle the InterventionProduct "deleted" event.
     */
    public function deleted(InterventionProduct $pivot): void
    {
        $this->updateStock($pivot, 'increment');
        $this->recalculateMaterialCost($pivot->intervention_id);
    }

    /**
     * Met à jour le stock (décrémente ou incrémente).
     *
     * @param InterventionProduct $pivot
     * @param string $operation 'increment' ou 'decrement'
     * @param float|null $quantity Quantité spécifique (sinon celle du pivot)
     * @throws ValidationException
     */
    private function updateStock(InterventionProduct $pivot, string $operation, ?float $quantity = null): void
    {
        $quantity = $quantity ?? $pivot->quantity;
        $intervention = Intervention::find($pivot->intervention_id);

        // 1. Trouver le dépôt par défaut de l'entreprise
        $warehouse = Warehouse::where('company_id', $intervention->company_id)
            ->where('is_default', true)
            ->first();

        // Si pas de dépôt par défaut, prendre le premier trouvé
        if (!$warehouse) {
            $warehouse = Warehouse::where('company_id', $intervention->company_id)->first();
        }

        if (!$warehouse) {
            throw ValidationException::withMessages([
                'product_id' => "Aucun dépôt trouvé pour effectuer le mouvement de stock.",
            ]);
        }

        // 2. Trouver la ligne de stock pour ce produit dans ce dépôt
        $stock = InventoryStock::where('product_id', $pivot->product_id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        // Si la ligne de stock n'existe pas, on la crée (seulement pour une incrémentation, impossible de décrémenter ce qui n'existe pas)
        if (!$stock) {
            if ($operation === 'decrement') {
                throw ValidationException::withMessages([
                    'product_id' => "Stock insuffisant (inexistant) dans le dépôt '{$warehouse->name}' pour le produit.",
                ]);
            }

            $stock = InventoryStock::create([
                'company_id' => $intervention->company_id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $pivot->product_id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);
        }

        // 3. Vérifier la disponibilité pour un décrément
        if ($operation === 'decrement' && $stock->quantity_on_hand < $quantity) {
             throw ValidationException::withMessages([
                'product_id' => "Stock insuffisant dans le dépôt '{$warehouse->name}'. Disponible: {$stock->quantity_on_hand}, Demandé: {$quantity}.",
            ]);
        }

        // 4. Appliquer le mouvement
        if ($operation === 'decrement') {
            $stock->decrement('quantity_on_hand', $quantity);
        } else {
            $stock->increment('quantity_on_hand', $quantity);
        }
    }

    private function recalculateMaterialCost(int $interventionId): void
    {
        $intervention = Intervention::find($interventionId);
        if ($intervention) {
            $intervention->recalculateMaterialCost();
        }
    }
}

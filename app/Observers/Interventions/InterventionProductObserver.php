<?php

namespace App\Observers\Interventions;

use App\Models\Articles\InventoryStock;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionProduct;
use Exception;

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

    private function updateStock(InterventionProduct $pivot, string $operation, ?float $quantity = null): void
    {
        $quantity = $quantity ?? $pivot->quantity;
        $intervention = Intervention::find($pivot->intervention_id);

        // On suppose que le stock est décrémenté du premier dépôt trouvé.
        // Une logique plus avancée pourrait être nécessaire.
        $stock = InventoryStock::where('product_id', $pivot->product_id)
            ->where('company_id', $intervention->company_id)
            ->first();

        if (!$stock) {
            throw new Exception("Stock introuvable pour le produit ID {$pivot->product_id}.");
        }

        if ($operation === 'decrement' && $stock->quantity_on_hand < $quantity) {
            throw new Exception("Stock insuffisant pour le produit ID {$pivot->product_id}.");
        }

        $stock->{$operation}('quantity_on_hand', $quantity);
    }

    private function recalculateMaterialCost(int $interventionId): void
    {
        $intervention = Intervention::find($interventionId);
        if ($intervention) {
            $intervention->recalculateMaterialCost();
        }
    }
}

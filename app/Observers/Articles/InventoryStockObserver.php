<?php

namespace App\Observers\Articles;

use App\Models\Articles\InventoryStock;
use App\Models\Articles\StockMovement;
use Illuminate\Support\Facades\Auth;

class InventoryStockObserver
{
    /**
     * Handle the InventoryStock "updated" event.
     *
     * @param  \App\Models\Articles\InventoryStock  $inventoryStock
     * @return void
     */
    public function updated(InventoryStock $inventoryStock): void
    {
        // On ne crée un mouvement que si la quantité physique a changé.
        if ($inventoryStock->isDirty('quantity_on_hand')) {
            $originalQuantity = $inventoryStock->getOriginal('quantity_on_hand');
            $newQuantity = $inventoryStock->quantity_on_hand;
            $change = $newQuantity - $originalQuantity;

            // On ne logue pas les mouvements nuls
            if ($change == 0) {
                return;
            }

            StockMovement::create([
                'company_id' => $inventoryStock->company_id,
                'product_id' => $inventoryStock->product_id,
                'warehouse_id' => $inventoryStock->warehouse_id,
                'quantity_change' => $change,
                'quantity_after' => $newQuantity,
                'user_id' => Auth::id(),
                // 'reason' et 'sourceable' seront à définir par le contexte qui déclenche le mouvement
                // Pour l'instant, on laisse vide. L'idéal serait d'avoir un service qui gère les mouvements.
            ]);
        }
    }
}

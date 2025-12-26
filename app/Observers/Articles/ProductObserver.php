<?php

namespace App\Observers\Articles;

use App\Models\Articles\InventoryStock;
use App\Models\Articles\Product;
use App\Models\Articles\ProductAssembly;
use App\Models\Articles\Warehouse;

class ProductObserver
{
    public function saving(Product $product): void
    {
        // Force la référence en majuscules et sans espaces superflus
        if ($product->isDirty('reference')) {
            $product->reference = strtoupper(trim($product->reference));
        }
    }

    /**
     * Handle the Product "created" event.
     *
     * @param  \App\Models\Articles\Product  $product
     * @return void
     */
    public function created(Product $product): void
    {
        // Si le produit est stockable, on crée une ligne de stock dans chaque dépôt de l'entreprise.
        if ($product->is_stockable) {
            $warehouses = Warehouse::where('company_id', $product->company_id)->get();

            foreach ($warehouses as $warehouse) {
                InventoryStock::create([
                    'company_id' => $product->company_id,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                ]);
            }
        }
    }

    public function deleting(Product $product): void
    {
        if ($product->isForceDeleting()) {
            // Nettoyage radical si suppression définitive
            $product->stocks()->delete();
            // On supprime les liens d'ouvrages où ce produit est impliqué
            ProductAssembly::where('parent_product_id', $product->id)
                ->orWhere('child_product_id', $product->id)
                ->delete();
        }
    }
}

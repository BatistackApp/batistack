<?php

namespace App\Observers\Articles;

use App\Models\Articles\Product;
use App\Models\Articles\ProductAssembly;

class ProductObserver
{
    public function saving(Product $product): void
    {
        // Force la référence en majuscules et sans espaces superflus
        if ($product->isDirty('reference')) {
            $product->reference = strtoupper(trim($product->reference));
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

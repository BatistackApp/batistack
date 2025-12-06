<?php

namespace App\Observers\Facturation;

use App\Models\Articles\Product;
use App\Models\Facturation\SalesDocumentLine;

class SalesDocumentLineObserver
{
    public function creating(SalesDocumentLine $line): void
    {
        // Auto-remplissage depuis le catalogue produit
        if ($line->product_id && empty($line->label)) {
            $product = Product::find($line->product_id);
            if ($product) {
                $line->label = $product->name;
                $line->description = $product->description; // Optionnel
                $line->unit = $product->unit->value ?? 'u';

                // Prix
                $line->unit_price = $product->selling_price;
                $line->vat_rate = $product->vat_rate;

                // CRUCIAL POUR LA MARGE : On fige le prix d'achat au moment du devis
                $line->buying_price = $product->buying_price;
            }
        }
    }

    public function saved(SalesDocumentLine $line): void
    {
        $line->document->recalculate();
    }

    public function deleted(SalesDocumentLine $line): void
    {
        $line->document->recalculate();
    }
}

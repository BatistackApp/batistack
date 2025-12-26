<?php

namespace App\Observers\Facturation;

use App\Models\Facturation\PurchaseDocumentLine;

class PurchaseDocumentLineObserver
{
    /**
     * Handle the PurchaseDocumentLine "created" event.
     */
    public function created(PurchaseDocumentLine $line): void
    {
        $line->purchaseDocument->recalculate();
    }

    /**
     * Handle the PurchaseDocumentLine "updated" event.
     */
    public function updated(PurchaseDocumentLine $line): void
    {
        $line->purchaseDocument->recalculate();
    }

    /**
     * Handle the PurchaseDocumentLine "deleted" event.
     */
    public function deleted(PurchaseDocumentLine $line): void
    {
        $line->purchaseDocument->recalculate();
    }
}

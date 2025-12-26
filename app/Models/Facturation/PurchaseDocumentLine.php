<?php

namespace App\Models\Facturation;

use App\Models\Articles\Product;
use App\Observers\Facturation\PurchaseDocumentLineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([PurchaseDocumentLineObserver::class])]
class PurchaseDocumentLine extends Model
{

    protected $guarded = [];

    public function purchaseDocument(): BelongsTo
    {
        return $this->belongsTo(PurchaseDocument::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'vat_rate' => 'decimal:2',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }
}

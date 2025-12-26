<?php

namespace App\Models\Articles;

use App\Models\Core\Company;
use App\Observers\Articles\InventoryStockObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([InventoryStockObserver::class])]
class InventoryStock extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = [];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Accesseur : Stock Disponible (Réel - Réservé)
    public function getAvailableAttribute()
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }
}

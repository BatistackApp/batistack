<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAssembly extends Model
{
    use HasFactory;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }

    protected $casts = [
        'quantity' => 'decimal:4',
    ];
}

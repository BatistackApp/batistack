<?php

namespace App\Models\Articles;

use App\Models\Core\Company;
use App\Observers\Articles\WarehouseObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([WarehouseObserver::class])]
class Warehouse extends Model
{
    use HasFactory, BelongsToCompany;
    protected $guarded = [];

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}

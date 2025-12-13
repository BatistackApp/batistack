<?php

namespace App\Models\GPAO;

use App\Enums\GPAO\ProductionOrderStatus;
use App\Models\Articles\Product;
use App\Models\Core\Company;
use App\Models\RH\Timesheet;
use App\Observers\GPAO\ProductionOrderObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ProductionOrderObserver::class])]
class ProductionOrder extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ProductionOrderStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'actual_start_date' => 'date',
            'actual_end_date' => 'date',
            'quantity' => 'decimal:2',
            'total_labor_cost' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function assignedTo(): MorphTo
    {
        return $this->morphTo();
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    // Méthode pour vérifier si l'OF est verrouillé (non modifiable)
    public function isLocked(): bool
    {
        return in_array($this->status, [ProductionOrderStatus::Completed, ProductionOrderStatus::Cancelled]);
    }
}

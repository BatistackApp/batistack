<?php

namespace App\Models\Chantiers;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChantierBudgetVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'budgeted_revenue' => 'decimal:2',
            'budgeted_labor_cost' => 'decimal:2',
            'budgeted_material_cost' => 'decimal:2',
            'budgeted_rental_cost' => 'decimal:2',
            'budgeted_purchase_cost' => 'decimal:2',
            'budgeted_fleet_cost' => 'decimal:2',
        ];
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calcule le coût total budgété pour cette version.
     */
    public function getTotalBudgetedCostAttribute(): float
    {
        return $this->budgeted_labor_cost +
               $this->budgeted_material_cost +
               $this->budgeted_rental_cost +
               $this->budgeted_purchase_cost +
               $this->budgeted_fleet_cost;
    }

    /**
     * Calcule la marge budgétée pour cette version.
     */
    public function getBudgetedMarginAttribute(): float
    {
        return $this->budgeted_revenue - $this->total_budgeted_cost;
    }
}

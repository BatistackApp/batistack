<?php

namespace App\Models\Chantiers;

use App\Enums\Chantiers\ChantiersStatus;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use App\Observers\Chantiers\ChantiersObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $total_fleet_cost
 */
#[ObservedBy([ChantiersObserver::class])]
class Chantiers extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;
    protected $guarded = [];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function projectModels(): HasMany
    {
        return $this->hasMany(ProjectModel::class);
    }

    protected function casts(): array
    {
        return [
            'date_start' => 'date',
            'end_date_planned' => 'date',
            'end_date_real' => 'date',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'total_labor_cost' => 'decimal:15,2',
            'total_rental_cost' => 'decimal:15,2',
            'total_sales_revenue' => 'decimal:15,2',
            'total_purchase_cost' => 'decimal:15,2',
            'total_material_cost' => 'decimal:15,2',
            'total_fleet_cost' => 'decimal:12,2',
            'budgeted_revenue' => 'decimal:15,2',
            'budgeted_labor_cost' => 'decimal:15,2',
            'budgeted_material_cost' => 'decimal:15,2',
            'budgeted_rental_cost' => 'decimal:15,2',
            'budgeted_purchase_cost' => 'decimal:15,2',
            'budgeted_fleet_cost' => 'decimal:12,2',
            'status' => ChantiersStatus::class,
            'is_overdue' => 'boolean'
        ];
    }

    // --- ACCESSEURS POUR LE SUIVI DE RENTABILITÉ ---

    /**
     * Calculate total real cost including labor, material, rental, purchase and fleet.
     *
     * @return float
     */
    public function getTotalRealCostAttribute(): float
    {
        return $this->total_labor_cost + $this->total_material_cost + $this->total_rental_cost + $this->total_purchase_cost + $this->total_fleet_cost;
    }

    /**
     * Calculate total budgeted cost including labor, material, rental, purchase and fleet.
     *
     * @return float
     */
    public function getTotalBudgetedCostAttribute(): float
    {
        return $this->budgeted_labor_cost + $this->budgeted_material_cost + $this->budgeted_rental_cost + $this->budgeted_purchase_cost + $this->budgeted_fleet_cost;
    }

    public function getRealMarginAttribute(): float
    {
        return $this->total_sales_revenue - $this->total_real_cost;
    }

    public function getBudgetedMarginAttribute(): float
    {
        return $this->budgeted_revenue - $this->total_budgeted_cost;
    }

    public function getMarginDifferenceAttribute(): float
    {
        return $this->real_margin - $this->budgeted_margin;
    }


    // Helper pour l'adresse complète
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address,
            $this->code_postal . ' ' . $this->ville
        ])->filter()->join(', ');
    }
}

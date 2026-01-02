<?php

namespace App\Models\Chantiers;

use App\Enums\Chantiers\ChantiersStatus;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use App\Observers\Chantiers\ChantiersObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function budgetVersions(): HasMany
    {
        return $this->hasMany(ChantierBudgetVersion::class)->orderByDesc('created_at');
    }

    protected function casts(): array
    {
        return [
            'date_start' => 'date',
            'end_date_planned' => 'date',
            'end_date_real' => 'date',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'total_labor_cost' => 'decimal:2',
            'total_rental_cost' => 'decimal:2',
            'total_sales_revenue' => 'decimal:2',
            'total_purchase_cost' => 'decimal:2',
            'total_material_cost' => 'decimal:2',
            'total_fleet_cost' => 'decimal:2',
            'budgeted_revenue' => 'decimal:2',
            'budgeted_labor_cost' => 'decimal:2',
            'budgeted_material_cost' => 'decimal:2',
            'budgeted_rental_cost' => 'decimal:2',
            'budgeted_purchase_cost' => 'decimal:2',
            'budgeted_fleet_cost' => 'decimal:2',
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

    // --- SCOPES POUR LA PERFORMANCE ---

    /**
     * Ajoute le calcul de la marge réelle directement dans la requête SQL.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithRealMargin(Builder $query): Builder
    {
        return $query->addSelect([
            'real_margin' => DB::raw('total_sales_revenue - (total_labor_cost + total_material_cost + total_rental_cost + total_purchase_cost + total_fleet_cost)')
        ]);
    }


    // Helper pour l'adresse complète
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address,
            $this->code_postal . ' ' . $this->ville
        ])->filter()->join(', ');
    }

    /**
     * Crée un snapshot du budget actuel.
     *
     * @param string $versionName Nom de la version (ex: "Budget Initial")
     * @param string|null $notes Notes optionnelles
     * @return ChantierBudgetVersion
     */
    public function createBudgetSnapshot(string $versionName, ?string $notes = null): ChantierBudgetVersion
    {
        return $this->budgetVersions()->create([
            'version_name' => $versionName,
            'notes' => $notes,
            'created_by' => Auth::id(),
            'budgeted_revenue' => $this->budgeted_revenue,
            'budgeted_labor_cost' => $this->budgeted_labor_cost,
            'budgeted_material_cost' => $this->budgeted_material_cost,
            'budgeted_rental_cost' => $this->budgeted_rental_cost,
            'budgeted_purchase_cost' => $this->budgeted_purchase_cost,
            'budgeted_fleet_cost' => $this->budgeted_fleet_cost,
        ]);
    }
}

<?php

namespace App\Models\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Articles\Product;
use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\Timesheet;
use App\Models\Tiers\Tiers;
use App\Observers\Interventions\InterventionObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([InterventionObserver::class])]
class Intervention extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => InterventionStatus::class,
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'actual_start_date' => 'date',
            'actual_end_date' => 'date',
            'total_labor_cost' => 'decimal:2',
            'total_material_cost' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Tiers::class, 'client_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'intervention_product')
            ->using(InterventionProduct::class) // Utilisation du modèle de pivot
            ->withPivot('quantity');
    }

    /**
     * Recalcule le coût total de la main-d'œuvre pour cette Intervention.
     */
    public function recalculateLaborCost(): void
    {
        $totalCost = $this->timesheets->sum(function (Timesheet $timesheet) {
            return $timesheet->cost;
        });

        $this->updateQuietly(['total_labor_cost' => $totalCost]);
    }

    /**
     * Recalcule le coût total des matériaux pour cette Intervention.
     */
    public function recalculateMaterialCost(): void
    {
        $totalCost = $this->products->sum(function (Product $product) {
            return $product->pivot->quantity * ($product->buying_price ?? 0);
        });

        $this->updateQuietly(['total_material_cost' => $totalCost]);
    }
}

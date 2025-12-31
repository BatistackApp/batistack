<?php

namespace App\Models\Fleets;

use App\Enums\Fleets\FleetType;
use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\Team;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property float $internal_daily_cost Coût journalier interne du véhicule pour imputation analytique
 * @property float $purchase_price
 * @property float $residual_value
 * @property int $depreciation_duration_years
 * @property \Illuminate\Support\Carbon|null $purchase_date
 */
class Fleet extends Model implements HasMedia
{
    use HasFactory, BelongsToCompany, InteractsWithMedia;
    protected $guarded = [];

    /**
     * Relations: Suivi de Maintenance
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function insurances(): HasMany
    {
        return $this->hasMany(Insurance::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(FleetAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(FleetAssignment::class)->whereNull('end_date')->latestOfMany();
    }

    public function employees(): MorphToMany
    {
        return $this->morphedByMany(Employee::class, 'assignable', 'fleet_assignments');
    }

    public function teams(): MorphToMany
    {
        return $this->morphedByMany(Team::class, 'assignable', 'fleet_assignments');
    }


    // --- CASTS ---

    protected function casts()
    {
        return [
            'purchase_date' => 'date',
            'is_available' => 'boolean',
            'last_check_date' => 'date',
            'type' => FleetType::class,
            'mileage' => 'integer',
            'internal_daily_cost' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'residual_value' => 'decimal:2',
            'current_value' => 'decimal:2',
            'depreciation_duration_years' => 'integer',
        ];
    }
}

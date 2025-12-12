<?php

namespace App\Models\Fleets;

use App\Enums\Fleets\FleetType;
use App\Models\Core\Company;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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

    /**
     * Relations: Contrats d'Assurance
     */
    public function insurances(): HasMany
    {
        return $this->hasMany(Insurance::class);
    }

    protected function casts()
    {
        return [
            'purchase_date' => 'date',
            'is_available' => 'boolean',
            'last_check_date' => 'date',
            'type' => FleetType::class,
            'mileage' => 'integer',
        ];
    }
}

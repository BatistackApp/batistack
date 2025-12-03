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
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ChantiersObserver::class])]
class Chantiers extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;
    protected $guarded = [];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    protected function casts(): array
    {
        return [
            'date_start' => 'date',
            'end_date_planned' => 'date',
            'end_date_real' => 'date',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'status' => ChantiersStatus::class,
            'is_overdue' => 'boolean'
        ];
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

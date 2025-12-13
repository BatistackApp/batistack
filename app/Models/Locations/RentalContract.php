<?php

namespace App\Models\Locations;

use App\Enums\Locations\RentalContractStatus;
use App\Models\Core\Company;
use App\Models\Fleets\Fleet;
use App\Models\Tiers\Tiers;
use App\Observers\Locations\RentalContractObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([RentalContractObserver::class])]
class RentalContract extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => RentalContractStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'daily_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    // Méthode pour vérifier si le contrat est verrouillé (non modifiable)
    public function isLocked(): bool
    {
        return in_array($this->status, [RentalContractStatus::Completed, RentalContractStatus::Cancelled]);
    }
}

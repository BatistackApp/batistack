<?php

namespace App\Models\Locations;

use App\Enums\Locations\RentalContractStatus;
use App\Enums\Locations\RentalPeriodicity;
use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use App\Observers\Locations\RentalContractObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            'periodicity' => RentalPeriodicity::class,
            'is_posted_to_compta' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
        ];
    }

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function chantiers(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RentalContractLine::class);
    }

    /**
     * Méthode centrale de recalcul des totaux du contrat.
     */
    public function recalculate(): void
    {
        $lines = $this->lines()->get();

        $total_ht = $lines->sum('total_ht');
        $total_vat = $lines->sum('total_vat');
        $total_ttc = $lines->sum('total_ttc');

        $this->updateQuietly([
            'total_ht' => $total_ht,
            'total_vat' => $total_vat,
            'total_ttc' => $total_ttc,
        ]);
    }
}

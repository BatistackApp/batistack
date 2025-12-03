<?php

namespace App\Models\Tiers;

use App\Enums\Tiers\TierNature;
use App\Models\Core\Company;
use App\Observers\Tiers\TiersObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([TiersObserver::class])]
class Tiers extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;
    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relations
    public function contacts()
    {
        return $this->hasMany(TierContact::class);
    }

    protected function casts()
    {
        return [
            'is_customer' => 'boolean',
            'is_supplier' => 'boolean',
            'is_subcontractor' => 'boolean',
            'is_active' => 'boolean',
            'outstanding_limit' => 'decimal:2',
            'nature' => TierNature::class,
        ];
    }

    // Helpers Métiers
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->zip_code . ' ' . $this->city
        ])->filter()->join(', ');
    }
}

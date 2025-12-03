<?php

namespace App\Models\Tiers;

use App\Models\Core\Company;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TierContact extends Model
{
    use HasFactory, BelongsToCompany;
    protected $guarded = [];

    public function tiers()
    {
        return $this->belongsTo(Tiers::class);
    }

    protected function casts()
    {
        return [
            'is_primary' => 'boolean',
            'receives_billing' => 'boolean',
        ];
    }

    // Accessor pour le nom complet
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}

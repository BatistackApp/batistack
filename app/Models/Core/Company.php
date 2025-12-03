<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(CompanyFeature::class);
    }

    // Helper pour vérifier rapidement si une option est active
    public function hasFeature(string $code): bool
    {
        return $this->features()
            ->where('feature_code', $code)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}

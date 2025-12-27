<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends Model
{

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_feature')->withPivot('value');
    }
}

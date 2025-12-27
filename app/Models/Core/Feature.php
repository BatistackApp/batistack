<?php

namespace App\Models\Core;

use App\Enums\Core\TypeFeature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends Model
{

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_optional' => 'boolean',
            'type' => TypeFeature::class,
        ];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_feature')->withPivot('value');
    }
}

<?php

namespace App\Models\Fleets;

use App\Observers\Fleets\InsuranceObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([InsuranceObserver::class])]
class Insurance extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'annual_cost' => 'decimal:2',
            'is_active' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }
}

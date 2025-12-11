<?php

namespace App\Models\Fleets;

use App\Observers\Fleets\MaintenanceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([MaintenanceObserver::class])]
class Maintenance extends Model
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
            'date_maintenance' => 'date',
            'next_date' => 'date',
            'cost' => 'decimal'
        ];
    }
}

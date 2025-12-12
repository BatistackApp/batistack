<?php

namespace App\Models\Fleets;

use App\Enums\Fleets\FleetAssignmentStatus;
use App\Observers\Fleets\FleetAssignmentObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy([FleetAssignmentObserver::class])]
class FleetAssignment extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => FleetAssignmentStatus::class,
            'notified_at' => 'datetime',
        ];
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }
}

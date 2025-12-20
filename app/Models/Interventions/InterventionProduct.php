<?php

namespace App\Models\Interventions;

use App\Observers\Interventions\InterventionProductObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[ObservedBy([InterventionProductObserver::class])]
class InterventionProduct extends Pivot
{
    protected $table = 'intervention_product';
}

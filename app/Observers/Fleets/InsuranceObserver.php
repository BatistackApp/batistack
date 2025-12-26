<?php

namespace App\Observers\Fleets;

use App\Models\Fleets\FleetCost;
use App\Models\Fleets\Insurance;

class InsuranceObserver
{
    /**
     * Handle the Insurance "created" event.
     */
    public function created(Insurance $insurance): void
    {
        if ($insurance->annual_cost > 0) {
            FleetCost::create([
                // L'assurance n'a pas de company_id, on le récupère via la flotte
                'company_id' => $insurance->fleet->company_id,
                'fleet_id' => $insurance->fleet_id,
                'amount' => $insurance->annual_cost,
                'date' => $insurance->start_date,
                'description' => "Assurance: {$insurance->insurer_name} ({$insurance->contract_number})",
                'sourceable_type' => Insurance::class,
                'sourceable_id' => $insurance->id,
            ]);
        }
    }
}

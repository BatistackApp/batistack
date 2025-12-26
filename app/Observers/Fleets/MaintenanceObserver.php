<?php

namespace App\Observers\Fleets;

use App\Models\Fleets\FleetCost;
use App\Models\Fleets\Maintenance;

class MaintenanceObserver
{
    /**
     * Handle the Maintenance "created" event.
     */
    public function created(Maintenance $maintenance): void
    {
        if ($maintenance->cost > 0) {
            FleetCost::create([
                'company_id' => $maintenance->company_id,
                'fleet_id' => $maintenance->fleet_id,
                'amount' => $maintenance->cost,
                'date' => $maintenance->maintenance_date,
                'description' => "Maintenance: {$maintenance->type->getLabel()}",
                'sourceable_type' => Maintenance::class,
                'sourceable_id' => $maintenance->id,
            ]);
        }
    }
}

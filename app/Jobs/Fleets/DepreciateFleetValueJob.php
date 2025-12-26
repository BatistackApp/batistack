<?php

namespace App\Jobs\Fleets;

use App\Models\Fleets\Fleet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DepreciateFleetValueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Durée de dépréciation en années
    const DEPRECIATION_YEARS = 5;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Début du calcul de la dépréciation de la flotte.");

        $fleetsToDepreciate = Fleet::whereNotNull('purchase_date')
            ->where('purchase_price', '>', 0)
            ->get();

        foreach ($fleetsToDepreciate as $fleet) {
            $purchaseDate = $fleet->purchase_date;
            $purchasePrice = $fleet->purchase_price;
            $monthsSincePurchase = $purchaseDate->diffInMonths(now());

            // Si le véhicule a plus de 5 ans, sa valeur est 0
            if ($monthsSincePurchase >= (self::DEPRECIATION_YEARS * 12)) {
                $newValue = 0;
            } else {
                // Calcul de la dépréciation mensuelle
                $monthlyDepreciation = $purchasePrice / (self::DEPRECIATION_YEARS * 12);
                $totalDepreciation = $monthlyDepreciation * $monthsSincePurchase;
                $newValue = $purchasePrice - $totalDepreciation;
            }

            // On s'assure que la valeur ne devient pas négative
            $newValue = max(0, $newValue);

            $fleet->update(['current_value' => $newValue]);
        }

        Log::info("Fin du calcul de la dépréciation. " . $fleetsToDepreciate->count() . " véhicules mis à jour.");
    }
}

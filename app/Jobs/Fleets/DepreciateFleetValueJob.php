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

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Début du calcul de la dépréciation et du TCO de la flotte.");

        $fleets = Fleet::whereNotNull('purchase_date')
            ->where('purchase_price', '>', 0)
            ->with(['insurances', 'maintenances'])
            ->get();

        foreach ($fleets as $fleet) {
            $this->processFleet($fleet);
        }

        Log::info("Fin du calcul de la dépréciation. " . $fleets->count() . " véhicules mis à jour.");
    }

    protected function processFleet(Fleet $fleet): void
    {
        $purchaseDate = $fleet->purchase_date;
        $purchasePrice = $fleet->purchase_price;
        $residualValue = $fleet->residual_value ?? 0;
        $durationYears = $fleet->depreciation_duration_years > 0 ? $fleet->depreciation_duration_years : 5;

        // 1. Calcul de la Valeur Actuelle (Dépréciation Linéaire)
        $monthsSincePurchase = $purchaseDate->diffInMonths(now());
        $totalDepreciableAmount = $purchasePrice - $residualValue;
        $monthlyDepreciation = $totalDepreciableAmount / ($durationYears * 12);

        $totalDepreciation = $monthlyDepreciation * $monthsSincePurchase;
        $newValue = $purchasePrice - $totalDepreciation;

        // La valeur ne peut pas descendre en dessous de la valeur résiduelle
        $newValue = max($residualValue, $newValue);

        // 2. Calcul du TCO Journalier (Coût de revient)

        // A. Coût de Dépréciation Journalier
        $dailyDepreciation = $totalDepreciableAmount / ($durationYears * 365);

        // B. Coût d'Assurance Journalier (Basé sur les contrats actifs)
        $annualInsuranceCost = $fleet->insurances()
            ->where('is_active', true)
            ->sum('annual_cost');

        $dailyInsurance = $annualInsuranceCost / 365;

        // C. Coût de Maintenance Journalier (Moyenne sur les 12 derniers mois)
        // On utilise 'cost' comme vérifié dans le modèle Maintenance
        $lastYearMaintenanceCost = $fleet->maintenances()
            ->where('date_maintenance', '>=', now()->subYear()) // Attention: le champ est date_maintenance
            ->sum('cost');

        $dailyMaintenance = $lastYearMaintenanceCost / 365;

        // Total TCO Journalier
        $internalDailyCost = $dailyDepreciation + $dailyInsurance + $dailyMaintenance;

        // Mise à jour
        $fleet->update([
            'current_value' => round($newValue, 2),
            'internal_daily_cost' => round($internalDailyCost, 2)
        ]);

        Log::debug("Fleet {$fleet->id}: Value={$newValue}, DailyCost={$internalDailyCost} (Depr:{$dailyDepreciation} + Ins:{$dailyInsurance} + Maint:{$dailyMaintenance})");
    }
}

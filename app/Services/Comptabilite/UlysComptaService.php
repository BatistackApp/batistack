<?php

namespace App\Services\Comptabilite;

use App\Enums\Fleets\UlysConsumptionStatus;
use App\Enums\Tiers\TierNature;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Fleets\UlysConsumption;
use App\Models\Tiers\Tiers;
use DB;
use Log;

class UlysComptaService
{
    /**
     * Tente de comptabiliser une consommation Ulys.
     * @param UlysConsumption $consumption
     * @return bool
     * @throws \Throwable
     */
    public function postUlysConsumptionEntry(UlysConsumption $consumption): bool
    {
        if ($consumption->status === UlysConsumptionStatus::Posted) {
            Log::warning("UlysConsumption {$consumption->id} est déjà comptabilisée. Skip.");
            return true;
        }

        $company = $consumption->company;

        // --- Logique pour trouver ou créer le Tiers "Ulys" ---
        $tier = Tiers::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Ulys'],
            [
                'nature' => TierNature::COMPANY,
                'is_active' => true,
                'is_supplier' => true,
            ]
        );

        $compteChargeId = $company->default_fuel_charge_account_id ?? 60610;
        $compteTvaId = $company->default_vat_account_id ?? 44566;
        $compteCreditId = $company->default_ulys_supplier_account_id ?? 40110;

        $journalId = $company->default_purchase_journal_id ?? 3;

        DB::beginTransaction();
        try {
            $totalTTC = $consumption->amount;
            $totalHT = round($totalTTC / 1.2, 2);
            $tva = $totalTTC - $totalHT;

            $reference = "ULYS-{$consumption->id}";
            $dateCompta = $consumption->transaction_date;

            // Écriture 1 : Débit - Compte de Charge (HT)
            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journalId,
                'account_id' => $compteChargeId,
                'date'       => $dateCompta,
                'label'      => "Frais Ulys - {$consumption->transaction_date->format('d-m-Y H:i')} (HT)",
                'debit'      => $totalHT,
                'credit'     => 0,
                'reference'  => $reference,
                'sourceable_type' => UlysConsumption::class,
                'sourceable_id' => $consumption->id,
            ]);

            // Écriture 2 : Débit - Compte de TVA
            if ($tva > 0) {
                ComptaEntry::create([
                    'company_id' => $company->id,
                    'journal_id' => $journalId,
                    'account_id' => $compteTvaId,
                    'date'       => $dateCompta,
                    'label'      => "TVA sur frais Ulys - {$consumption->transaction_date->format('d-m-Y H:i')}",
                    'debit'      => $tva,
                    'credit'     => 0,
                    'reference'  => $reference,
                    'sourceable_type' => UlysConsumption::class,
                    'sourceable_id' => $consumption->id,
                ]);
            }

            // Écriture 3 : Crédit - Compte Fournisseur Ulys (TTC)
            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journalId,
                'account_id' => $compteCreditId,
                'tier_id'    => $tier->id, // <-- Association du Tiers
                'date'       => $dateCompta,
                'label'      => "Frais Ulys - {$consumption->transaction_date->format('d-m-Y H:i')} (TTC)",
                'debit'      => 0,
                'credit'     => $totalTTC,
                'reference'  => $reference,
                'sourceable_type' => UlysConsumption::class,
                'sourceable_id' => $consumption->id,
            ]);

            $consumption->update(['status' => UlysConsumptionStatus::Posted]);

            DB::commit();
            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Erreur de comptabilisation UlysConsumption {$consumption->id}: " . $e->getMessage());
            return false;
        }
    }
}

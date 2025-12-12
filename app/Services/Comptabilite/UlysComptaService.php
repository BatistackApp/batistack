<?php

namespace App\Services\Comptabilite;

use App\Enums\Fleets\UlysConsumptionStatus;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Fleets\UlysConsumption;
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
        // 1. Vérifications préliminaires
        if ($consumption->status === UlysConsumptionStatus::Posted) {
            Log::warning("UlysConsumption {$consumption->id} est déjà comptabilisée. Skip.");
            return true;
        }

        $company = $consumption->company;

        // TODO: Utiliser des comptes configurables par l'entreprise
        $compteChargeId = $company->default_fuel_charge_account_id ?? 60610; // Compte Carburant
        $compteTvaId    = $company->default_vat_account_id ?? 44566;
        $compteCreditId = $company->default_ulys_supplier_account_id ?? 40110; // Fournisseur Ulys

        $journalId = $company->default_purchase_journal_id ?? 3; // Journal d'Achat

        DB::beginTransaction();
        try {
            // Pour l'instant, on suppose que le montant est TTC et la TVA est à 20%
            // Une logique plus fine sera nécessaire pour extraire la TVA réelle
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
                'label'      => "Frais Ulys - {$consumption->description} (HT)",
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
                    'label'      => "TVA sur frais Ulys - {$consumption->description}",
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
                'date'       => $dateCompta,
                'label'      => "Frais Ulys - {$consumption->description} (TTC)",
                'debit'      => 0,
                'credit'     => $totalTTC,
                'reference'  => $reference,
                'sourceable_type' => UlysConsumption::class,
                'sourceable_id' => $consumption->id,
            ]);

            // Marquer la consommation comme comptabilisée
            $consumption->update(['status' => UlysConsumptionStatus::Posted]);

            DB::commit();
            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Erreur de comptabilisation UlysConsumption {$consumption->id}: " . $e->getMessage());
            // TODO: Ajouter une notification Filament aux Gestionnaires Financiers
            return false;
        }
    }
}

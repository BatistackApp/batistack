<?php

namespace App\Services\Comptabilite;

use App\Enums\Comptabilite\JournalType;
use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Interventions\Intervention;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterventionComptaService
{
    /**
     * Crée les écritures comptables de coûts pour une intervention terminée.
     *
     * @param Intervention $intervention
     * @return void
     * @throws Exception
     */
    public function postInterventionCosts(Intervention $intervention): void
    {
        if ($intervention->costs_posted_to_compta) {
            Log::warning("Les coûts pour l'intervention {$intervention->id} ont déjà été comptabilisés.");
            return;
        }

        $company = $intervention->company;

        // Récupérer le journal des Opérations Diverses (OD)
        $journal = ComptaJournal::where('company_id', $company->id)
            ->where('type', JournalType::General)
            ->first();

        if (!$journal) {
            throw new Exception("Journal d'Opérations Diverses non trouvé pour la compagnie {$company->name}.");
        }

        // Récupérer les comptes comptables nécessaires (à adapter)
        $laborChargeAccount = ComptaAccount::where('company_id', $company->id)->where('number', '604000')->firstOrFail(); // Prestations de services
        $materialChargeAccount = ComptaAccount::where('company_id', $company->id)->where('number', '607000')->firstOrFail(); // Achats de marchandises
        $laborPayableAccount = ComptaAccount::where('company_id', $company->id)->where('number', '421000')->firstOrFail(); // Personnel - Rémunérations dues
        $stockAccount = ComptaAccount::where('company_id', $company->id)->where('number', '370000')->firstOrFail(); // Stocks de marchandises

        DB::transaction(function () use ($intervention, $journal, $laborChargeAccount, $materialChargeAccount, $laborPayableAccount, $stockAccount) {
            $reference = "INT-COST-{$intervention->id}";

            // Écriture pour le coût de la main-d'œuvre
            if ($intervention->total_labor_cost > 0) {
                // Débit : Compte de charge de main-d'œuvre
                ComptaEntry::create([
                    'company_id' => $intervention->company_id,
                    'journal_id' => $journal->id,
                    'account_id' => $laborChargeAccount->id,
                    'date' => $intervention->actual_end_date ?? now(),
                    'label' => "Coût M.O. Intervention {$intervention->title}",
                    'debit' => $intervention->total_labor_cost,
                    'credit' => 0,
                    'sourceable_type' => Intervention::class,
                    'sourceable_id' => $intervention->id,
                    'reference' => $reference,
                ]);
                // Crédit : Compte de personnel à payer
                ComptaEntry::create([
                    'company_id' => $intervention->company_id,
                    'journal_id' => $journal->id,
                    'account_id' => $laborPayableAccount->id,
                    'date' => $intervention->actual_end_date ?? now(),
                    'label' => "Contrepartie M.O. Intervention {$intervention->title}",
                    'debit' => 0,
                    'credit' => $intervention->total_labor_cost,
                    'sourceable_type' => Intervention::class,
                    'sourceable_id' => $intervention->id,
                    'reference' => $reference,
                ]);
            }

            // Écriture pour le coût des matériaux
            if ($intervention->total_material_cost > 0) {
                // Débit : Compte de charge de matériaux
                ComptaEntry::create([
                    'company_id' => $intervention->company_id,
                    'journal_id' => $journal->id,
                    'account_id' => $materialChargeAccount->id,
                    'date' => $intervention->actual_end_date ?? now(),
                    'label' => "Coût Matériaux Intervention {$intervention->title}",
                    'debit' => $intervention->total_material_cost,
                    'credit' => 0,
                    'sourceable_type' => Intervention::class,
                    'sourceable_id' => $intervention->id,
                    'reference' => $reference,
                ]);
                // Crédit : Compte de stock
                ComptaEntry::create([
                    'company_id' => $intervention->company_id,
                    'journal_id' => $journal->id,
                    'account_id' => $stockAccount->id,
                    'date' => $intervention->actual_end_date ?? now(),
                    'label' => "Sortie Stock Intervention {$intervention->title}",
                    'debit' => 0,
                    'credit' => $intervention->total_material_cost,
                    'sourceable_type' => Intervention::class,
                    'sourceable_id' => $intervention->id,
                    'reference' => $reference,
                ]);
            }

            // Marquer les coûts comme comptabilisés
            $intervention->update(['costs_posted_to_compta' => true]);
        });
    }
}

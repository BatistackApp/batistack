<?php

namespace App\Services\Comptabilite;

use App\Models\Paie\PayrollPeriod;
use Illuminate\Support\Facades\DB;

class PayrollComptaService
{
    /**
     * Génère l'écriture de paie (OD de salaires) pour une période donnée.
     *
     * @param PayrollPeriod $period
     * @throws \Throwable
     */
    public function postPayrollEntry(PayrollPeriod $period): void
    {
        // TODO: Vérifier que la période est verrouillée et non déjà comptabilisée.

        $company = $period->company;
        $journal = ComptaJournal::where('company_id', $company->id)->where('type', 'od')->firstOrFail(); // Journal d'Opérations Diverses

        // Récupérer tous les comptes nécessaires (simplification)
        $chargeAccount = ComptaAccount::where('company_id', $company->id)->where('number', '641000')->firstOrFail();
        $employeeAccount = ComptaAccount::where('company_id', $company->id)->where('number', '421000')->firstOrFail();
        $urssafAccount = ComptaAccount::where('company_id', $company->id)->where('number', '431000')->firstOrFail();

        // Agréger toutes les variables de la période
        $variables = $period->variables()
            ->select('type', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('type')
            ->get();

        // --- Logique de calcul de la paie brute, des charges, du net... ---
        // C'est une logique très complexe qui dépend de la législation.
        // Voici une simplification extrême.
        $salaireBrut = $variables->where('type', 'std_hour')->first()->total_quantity * 20; // Taux horaire brut moyen ?
        $chargesSalariales = $salaireBrut * 0.22;
        $chargesPatronales = $salaireBrut * 0.45;
        $netAPayer = $salaireBrut - $chargesSalariales;

        DB::transaction(function () use ($period, $journal, $chargeAccount, $employeeAccount, $urssafAccount, $salaireBrut, $chargesPatronales, $netAPayer, $company) {
            $label = "OD de Salaires - {$period->name}";
            $date = $period->end_date;

            // 1. Débit : Compte de charge "Rémunérations du personnel" (Brut)
            ComptaEntry::create([
                'company_id' => $company->id, 'journal_id' => $journal->id, 'account_id' => $chargeAccount->id,
                'date' => $date, 'label' => $label, 'debit' => $salaireBrut, 'credit' => 0,
            ]);

            // 2. Débit : Compte de charge "Charges sociales" (Patronales)
            ComptaEntry::create([
                'company_id' => $company->id, 'journal_id' => $journal->id, 'account_id' => $urssafAccount->id, // Simplification
                'date' => $date, 'label' => $label, 'debit' => $chargesPatronales, 'credit' => 0,
            ]);

            // 3. Crédit : Compte de tiers "Personnel - Rémunérations dues" (Net à payer)
            ComptaEntry::create([
                'company_id' => $company->id, 'journal_id' => $journal->id, 'account_id' => $employeeAccount->id,
                'date' => $date, 'label' => $label, 'debit' => 0, 'credit' => $netAPayer,
            ]);

            // 4. Crédit : Compte de tiers "URSSAF" (Total des charges)
            ComptaEntry::create([
                'company_id' => $company->id, 'journal_id' => $journal->id, 'account_id' => $urssafAccount->id,
                'date' => $date, 'label' => $label, 'debit' => 0, 'credit' => $chargesSalariales + $chargesPatronales,
            ]);
        });
    }
}

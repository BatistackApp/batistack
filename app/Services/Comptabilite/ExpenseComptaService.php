<?php

namespace App\Services\Comptabilite;

use App\Enums\NoteFrais\ExpenseStatus;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\NoteFrais\Expense;
use DB;
use Log;

class ExpenseComptaService
{
    /**
     * Tente de comptabiliser une Note de Frais validée.
     * @param Expense $expense
     * @return bool
     * @throws \Throwable
     */
    public function postExpenseEntry(Expense $expense): bool
    {
        // 1. Vérifications préliminaires
        if ($expense->status === ExpenseStatus::Posted->value) {
            Log::warning("Expense {$expense->id} est déjà comptabilisée. Skip.");
            return true;
        }

        // Assumer que les ID des comptes sont configurés dans la company ou ailleurs
        // Pour cet exemple, nous utiliserons des ID de comptes par défaut (à adapter)
        $company = $expense->company;

        // Exemple d'ID de comptes (Devrait venir d'une configuration métier)
        $compteChargeId  = $company->default_expense_charge_account_id ?? 60000;
        $compteTvaId     = $company->default_vat_account_id ?? 44566;
        $compteCreditId  = $company->default_supplier_account_id ?? 40100; // Ou Compte Employé 42100 si c'est un remboursement

        // Assumons que le journal d'achat (ACH) est utilisé, ou un journal OD spécifique.
        $journalId = $company->default_purchase_journal_id ?? 3;

        DB::beginTransaction();
        try {
            $totalTTC = $expense->amount_ttc;
            $totalHT = $expense->amount_ht;
            $tva = $expense->vat_amount;

            $reference = "NDF-{$expense->id}";
            $dateCompta = $expense->validated_at ?? $expense->date; // Date de validation ou date de la dépense

            // Écriture 1 : Débit - Compte de Charge (HT)
            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journalId,
                'account_id' => $compteChargeId,
                'date'       => $dateCompta,
                'label'      => "NDF {$expense->employee->full_name} - {$expense->label} (HT)",
                'debit'      => $totalHT,
                'credit'     => 0,
                'reference'  => $reference,
                'sourceable_type' => Expense::class,
                'sourceable_id' => $expense->id,
            ]);

            // Écriture 2 : Débit - Compte de TVA (TVA Déductible)
            if ($tva > 0) {
                ComptaEntry::create([
                    'company_id' => $company->id,
                    'journal_id' => $journalId,
                    'account_id' => $compteTvaId,
                    'date'       => $dateCompta,
                    'label'      => "NDF TVA - {$expense->label}",
                    'debit'      => $tva,
                    'credit'     => 0,
                    'reference'  => $reference,
                    'sourceable_type' => Expense::class,
                    'sourceable_id' => $expense->id,
                ]);
            }

            // Écriture 3 : Crédit - Compte Fournisseur / Employé (TTC)
            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journalId,
                'account_id' => $compteCreditId,
                'date'       => $dateCompta,
                'label'      => "NDF {$expense->employee->full_name} - {$expense->label} (TTC)",
                'debit'      => 0,
                'credit'     => $totalTTC,
                'reference'  => $reference,
                'sourceable_type' => Expense::class,
                'sourceable_id' => $expense->id,
            ]);

            // Marquer la note de frais comme comptabilisée
            $expense->update(['status' => ExpenseStatus::Posted]);

            DB::commit();
            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Erreur de comptabilisation NDF {$expense->id}: " . $e->getMessage());
            // TODO: Ajouter une notification Filament aux Gestionnaires Financiers
            return false;
        }
    }
}

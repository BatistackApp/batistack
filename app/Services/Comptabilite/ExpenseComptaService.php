<?php

namespace App\Services\Comptabilite;

use App\Enums\NoteFrais\ExpenseStatus;
use App\Enums\Tiers\TierNature;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\NoteFrais\Expense;
use App\Models\Tiers\Tiers;
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
        if ($expense->status === ExpenseStatus::Posted) {
            Log::warning("Expense {$expense->id} est déjà comptabilisée. Skip.");
            return true;
        }

        // Assumer que les ID des comptes sont configurés dans la company ou ailleurs
        // Pour cet exemple, nous utiliserons des ID de comptes par défaut (à adapter)
        $company = $expense->company;
        $employee = $expense->employee;

        // --- Logique pour trouver ou créer le Tiers correspondant à l'employé ---
        $tier = Tiers::firstOrCreate(
            ['company_id' => $company->id, 'email' => $employee->email],
            [
                'name' => $employee->full_name,
                'nature' => TierNature::Employee,
                'is_active' => true,
            ]
        );

        $compteChargeId = $company->default_expense_charge_account_id ?? 60000;
        $compteTvaId = $company->default_vat_account_id ?? 44566;
        $compteCreditId = $company->default_employee_account_id ?? 42100; // Compte Employé

        $journalId = $company->default_purchase_journal_id ?? 3;

        DB::beginTransaction();
        try {
            $totalTTC = $expense->amount_ttc;
            $totalHT = $expense->amount_ht;
            $tva = $expense->vat_amount;

            $reference = "NDF-{$expense->id}";
            $dateCompta = $expense->validated_at ?? $expense->date;

            // Écriture 1 : Débit - Compte de Charge (HT)
            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journalId,
                'account_id' => $compteChargeId,
                'date'       => $dateCompta,
                'label'      => "NDF {$employee->full_name} - {$expense->label} (HT)",
                'debit'      => $totalHT,
                'credit'     => 0,
                'reference'  => $reference,
                'sourceable_type' => Expense::class,
                'sourceable_id' => $expense->id,
            ]);

            // Écriture 2 : Débit - Compte de TVA
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

            // Écriture 3 : Crédit - Compte Employé (TTC)
            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journalId,
                'account_id' => $compteCreditId,
                'tier_id'    => $tier->id, // <-- Association du Tiers
                'date'       => $dateCompta,
                'label'      => "NDF {$employee->full_name} - {$expense->label} (TTC)",
                'debit'      => 0,
                'credit'     => $totalTTC,
                'reference'  => $reference,
                'sourceable_type' => Expense::class,
                'sourceable_id' => $expense->id,
            ]);

            $expense->update(['status' => ExpenseStatus::Posted]);

            DB::commit();
            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Erreur de comptabilisation NDF {$expense->id}: " . $e->getMessage());
            return false;
        }
    }
}

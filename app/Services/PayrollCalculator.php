<?php

namespace App\Services;

use App\Enums\Paie\PayrollVariableType;
use App\Models\NoteFrais\Expense;
use App\Models\Paie\PayrollSlip;
use App\Models\RH\Timesheet;
use Illuminate\Support\Facades\DB;

class PayrollCalculator
{
    public function calculate(PayrollSlip $slip): void
    {
        // 1. Nettoyage des anciennes variables (pour permettre le recalcul)
        $slip->variables()->delete();
        $totalHours = 0;
        $totalExpensesAmount = 0;

        // Récupération de la période
        $startDate = $slip->period->start_date;
        $endDate = $slip->period->end_date;
        $employeeId = $slip->employee_id;

        // --- 2. Intégration du Module POINTAGE / Timesheets ---
        // Le but est d'agréger toutes les heures de l'employé pour la période.
        $this->processTimesheets($slip, $startDate, $endDate, $employeeId, $totalHours);

        // --- 3. Intégration du Module NOTES DE FRAIS ---
        // Le but est de créer une variable pour chaque note de frais validée et à rembourser.
        $this->processExpenses($slip, $startDate, $endDate, $employeeId, $totalExpensesAmount);

        // --- 4. Mise à jour des totaux sur le bulletin ---
        $slip->update([
            'total_hours' => $totalHours,
            'total_expenses_amount' => $totalExpensesAmount,
        ]);

        // Optionnel : Créer une notification pour l'employé si le bulletin est "validé" (is_validated)
    }

    /**
     * Traite les pointages et crée les variables d'heures.
     */
    protected function processTimesheets(PayrollSlip $slip, $startDate, $endDate, $employeeId, &$totalHours): void
    {
        // ⚠️ NOTE TECHNIQUE : Le modèle Timesheet n'a pas été fourni. Nous utilisons un placeholder.
        // La requête réelle dépendra de la structure de votre table Timesheets (pointage).
        $timesheets = Timesheet::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Simuler l'agrégation des heures par type
        $aggregatedHours = [
            PayrollVariableType::StandardHour->value => 0,
            PayrollVariableType::Overtime25->value => 0,
            // ... autres types d'heures
        ];

        foreach ($timesheets as $ts) {
            // Ici, une logique complexe de calcul des heures sup est généralement nécessaire,
            // basée sur le contrat de l'employé et les règles légales.
            // Pour l'exemple, nous allons juste ajouter les heures normales.
            $aggregatedHours[PayrollVariableType::StandardHour->value] += $ts->hours_worked; // Exemple
        }

        // Création des variables de paie pour les heures
        foreach ($aggregatedHours as $typeValue => $quantity) {
            if ($quantity > 0) {
                $type = PayrollVariableType::from($typeValue);
                $slip->variables()->create([
                    'type' => $type,
                    'code' => $this->getPayrollCode($type), // Fonction à implémenter pour les codes export
                    'label' => $type->getLabel(),
                    'quantity' => $quantity,
                    'unit' => 'h',
                    'source' => 'Pointage',
                ]);
                $totalHours += $quantity;
            }
        }
    }

    /**
     * Traite les notes de frais validées et à rembourser.
     */
    protected function processExpenses(PayrollSlip $slip, $startDate, $endDate, $employeeId, &$totalExpensesAmount): void
    {
        // Récupération des notes de frais VALIDÉES, NON REMBOURSÉES et APPARTENANT à la période.
        // Supposons une colonne `reimbursed_at` qui est NULL si non payé.
        $validatedExpenses = Expense::where('employee_id', $employeeId)
            ->where('status', 'validated') // Statut fourni par le module Note de Frais
            ->whereNull('reimbursed_at')
            ->whereBetween(DB::raw('DATE(validated_at)'), [$startDate, $endDate]) // Utilisez la date de validation
            ->get();

        foreach ($validatedExpenses as $expense) {
            $amount = $expense->amount_to_reimburse; // Montant net à rembourser

            $slip->variables()->create([
                'type' => PayrollVariableType::ExpenseRefund,
                'code' => 'RFRAIS',
                'label' => 'Remboursement Note de Frais: ' . $expense->label,
                'quantity' => $amount,
                'unit' => '€',
                'source' => 'NotesDeFrais',
                // Utilisation de la relation Polymorphique pour la traçabilité
                'sourceable_type' => Expense::class,
                'sourceable_id' => $expense->id,
            ]);
            $totalExpensesAmount += $amount;

            // ⚠️ AUTOMATISME CRUCIAL : Marquer la note de frais comme "en cours de remboursement"
            $expense->update(['reimbursed_at' => now(), 'reimbursed_by_payroll_slip_id' => $slip->id]);
        }
    }

    /**
     * Renvoie le code d'export de paie (ex: HN, HS25) basé sur le type.
     * Cette fonction devra être adaptée selon le logiciel de paie cible (Silae, Sage, etc.).
     */
    protected function getPayrollCode(PayrollVariableType $type): string
    {
        return match ($type) {
            PayrollVariableType::StandardHour => 'HN',
            PayrollVariableType::Overtime25 => 'HS25',
            PayrollVariableType::Overtime50 => 'HS50',
            PayrollVariableType::NightHour => 'HDN',
            PayrollVariableType::SundayHour => 'HDIM',
            PayrollVariableType::Absence => 'ABS',
            PayrollVariableType::Bonus => 'PRIME',
            PayrollVariableType::ExpenseRefund => 'RFRAIS',
        };
    }
}

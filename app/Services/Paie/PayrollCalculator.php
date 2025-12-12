<?php

namespace App\Services\Paie;

use App\Enums\Paie\PayrollVariableType;
use App\Enums\RH\TimesheetType;
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
        $this->processTimesheets($slip, $startDate, $endDate, $employeeId, $totalHours);

        // --- 3. Intégration du Module NOTES DE FRAIS ---
        $this->processExpenses($slip, $startDate, $endDate, $employeeId, $totalExpensesAmount);

        // --- 4. Mise à jour des totaux sur le bulletin ---
        $slip->update([
            'total_hours' => $totalHours,
            'total_expenses_amount' => $totalExpensesAmount,
        ]);
    }

    /**
     * Traite les pointages et crée les variables d'heures.
     */
    protected function processTimesheets(PayrollSlip $slip, $startDate, $endDate, $employeeId, &$totalHours): void
    {
        $timesheets = Timesheet::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('type', DB::raw('SUM(hours) as total_hours'))
            ->groupBy('type')
            ->get();

        foreach ($timesheets as $ts) {
            $payrollType = $this->mapTimesheetTypeToPayrollVariable($ts->type);

            if ($payrollType) {
                $slip->variables()->create([
                    'type' => $payrollType,
                    'code' => $this->getPayrollCode($payrollType),
                    'label' => $payrollType->getLabel(),
                    'quantity' => $ts->total_hours,
                    'unit' => 'h',
                    'source' => 'Pointage',
                ]);
                $totalHours += $ts->total_hours;
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
                'sourceable_type' => Expense::class,
                'sourceable_id' => $expense->id,
            ]);
            $totalExpensesAmount += $amount;

            // La mise à jour de reimbursed_at et reimbursed_by_payroll_slip_id sera faite dans le Job d'export
        }
    }

    /**
     * Fait la correspondance entre le type d'heure du pointage et la variable de paie.
     */
    protected function mapTimesheetTypeToPayrollVariable(TimesheetType $type): ?PayrollVariableType
    {
        return match ($type) {
            TimesheetType::Work, TimesheetType::Travel => PayrollVariableType::StandardHour,
            TimesheetType::Overtime25 => PayrollVariableType::Overtime25,
            TimesheetType::Overtime50 => PayrollVariableType::Overtime50,
            TimesheetType::NightHour => PayrollVariableType::NightHour,
            TimesheetType::SundayHour => PayrollVariableType::SundayHour,
            TimesheetType::Absence => PayrollVariableType::Absence,
            default => null, // On ignore les autres types comme 'Training' pour l'instant
        };
    }

    /**
     * Renvoie le code d'export de paie (ex: HN, HS25) basé sur le type.
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

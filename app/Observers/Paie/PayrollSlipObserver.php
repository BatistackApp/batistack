<?php

namespace App\Observers\Paie;

use App\Jobs\Paie\CalculatePayrollSlipJob;
use App\Models\Paie\PayrollSlip;
use App\Services\Paie\PayrollCalculator;

class PayrollSlipObserver
{

    public function __construct(public PayrollCalculator $calculator)
    {
    }

    public function updated(PayrollSlip $payrollSlip): void
    {
        if ($payrollSlip->wasChanged('is_validated') && $payrollSlip->is_validated) {
            // Notification Paie : Prévenir le responsable que ce bulletin est prêt pour l'export.
            // Notification::send(User::where('role', 'payroll_manager')->get(), new PayrollSlipValidated($payrollSlip));
        }
    }

    public function saving(PayrollSlip $payrollSlip): void
    {
        if (!$payrollSlip->is_validated) {
            $importantFieldsChanged = $payrollSlip->isDirty() && collect($payrollSlip->getDirty())
                ->except(['manager_comment', 'updated_at', 'created_at'])
                ->isNotEmpty();

            if ($payrollSlip->isDirty('payroll_period_id') || $payrollSlip->isDirty('employee_id') || $importantFieldsChanged || $payrollSlip->wasRecentlyCreated) {
                CalculatePayrollSlipJob::dispatch($payrollSlip);
            }
        }
    }
}

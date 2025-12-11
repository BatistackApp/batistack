<?php

namespace App\Jobs\Paie;

use App\Models\Paie\PayrollSlip;
use App\Services\PayrollCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculatePayrollSlipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(public PayrollSlip $payrollSlip)
    {
    }

    public function handle(PayrollCalculator $calculator): void
    {
        if ($this->payrollSlip->is_validated) {
            return;
        }

        $calculator->calculate($this->payrollSlip);

        Log::info("Payroll Slip Recalculated.", ['slip_id' => $this->payrollSlip->id, 'period' => $this->payrollSlip->period->name]);
    }
}

<?php

namespace App\Services;

use App\Models\Paie\PayrollSlip;

class PayrollCalculator
{
    public function calculate(PayrollSlip $slip): void
    {
        // 1. Nettoyage des anciennes variables (pour permettre le recalcul)
        $slip->variables()->delete();
    }
}

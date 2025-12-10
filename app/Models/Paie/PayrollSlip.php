<?php

namespace App\Models\Paie;

use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSlip extends Model
{
    use HasFactory;

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriods::class, 'payroll_period_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts()
    {
        return [
            'is_validated' => 'boolean',
        ];
    }
}

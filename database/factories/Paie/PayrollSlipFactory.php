<?php

namespace Database\Factories\Paie;

use App\Models\Paie\PayrollPeriods;
use App\Models\Paie\PayrollSlip;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PayrollSlipFactory extends Factory
{
    protected $model = PayrollSlip::class;

    public function definition()
    {
        return [
            'total_hours' => $this->faker->randomFloat(),
            'total_expenses_amount' => $this->faker->randomFloat(),
            'manager_comment' => $this->faker->word(),
            'is_validated' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'payroll_period_id' => PayrollPeriods::factory(),
            'employee_id' => Employee::factory(),
        ];
    }
}

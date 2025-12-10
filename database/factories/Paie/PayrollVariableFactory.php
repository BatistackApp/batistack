<?php

namespace Database\Factories\Paie;

use App\Models\Paie\PayrollSlip;
use App\Models\Paie\PayrollVariable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PayrollVariableFactory extends Factory
{
    protected $model = PayrollVariable::class;

    public function definition()
    {
        return [
            'type' => $this->faker->word(),
            'code' => $this->faker->word(),
            'label' => $this->faker->word(),
            'quantity' => $this->faker->randomFloat(),
            'unit' => $this->faker->word(),
            'unit_value' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'payroll_slip_id' => PayrollSlip::factory(),
        ];
    }
}

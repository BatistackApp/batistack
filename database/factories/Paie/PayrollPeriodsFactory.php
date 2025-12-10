<?php

namespace Database\Factories\Paie;

use App\Models\Core\Company;
use App\Models\Paie\PayrollPeriods;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PayrollPeriodsFactory extends Factory
{
    protected $model = PayrollPeriods::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now(),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
        ];
    }
}

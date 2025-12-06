<?php

namespace Database\Factories\RH;

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\Timesheet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TimesheetFactory extends Factory
{
    protected $model = Timesheet::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'type' => $this->faker->word(),
            'hours' => $this->faker->randomFloat(),
            'lunch_basket' => $this->faker->boolean(),
            'travel_zone' => $this->faker->boolean(),
            'is_validated' => $this->faker->boolean(),
            'validated_at' => Carbon::now(),
            'comment' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'chantiers_id' => Chantiers::factory(),
        ];
    }
}

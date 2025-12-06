<?php

namespace Database\Factories\RH;

use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'nir' => $this->faker->word(),
            'job_title' => $this->faker->word(),
            'contract_type' => $this->faker->word(),
            'entry_date' => Carbon::now(),
            'exit_date' => Carbon::now(),
            'hourly_cost' => $this->faker->randomFloat(),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'user_id' => User::factory(),
        ];
    }
}

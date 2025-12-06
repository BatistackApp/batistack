<?php

namespace Database\Factories\NoteFrais;

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\NoteFrais\Expense;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'category' => $this->faker->word(),
            'label' => $this->faker->word(),
            'amount_ht' => $this->faker->randomFloat(),
            'vat_amount' => $this->faker->randomFloat(),
            'amount_ttc' => $this->faker->randomFloat(),
            'proof_path' => $this->faker->word(),
            'status' => $this->faker->word(),
            'rejection_reason' => $this->faker->word(),
            'is_billable' => $this->faker->boolean(),
            'has_been_billed' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'chantiers_id' => Chantiers::factory(),
        ];
    }
}

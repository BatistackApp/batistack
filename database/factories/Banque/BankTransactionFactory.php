<?php

namespace Database\Factories\Banque;

use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BankTransactionFactory extends Factory
{
    protected $model = BankTransaction::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'label' => $this->faker->word(),
            'amount' => $this->faker->randomFloat(),
            'category' => $this->faker->word(),
            'external_id' => $this->faker->word(),
            'raw_data' => $this->faker->words(),
            'reconciled_at' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'bank_account_id' => BankAccount::factory(),
        ];
    }
}

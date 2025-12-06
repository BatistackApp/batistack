<?php

namespace Database\Factories\Banque;

use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use App\Models\Banque\Payment;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(),
            'method' => $this->faker->word(),
            'reference' => $this->faker->word(),
            'date' => Carbon::now(),
            'note' => $this->faker->word(),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'tiers_id' => Tiers::factory(),
            'bank_account_id' => BankAccount::factory(),
            'bank_transaction_id' => BankTransaction::factory(),
        ];
    }
}

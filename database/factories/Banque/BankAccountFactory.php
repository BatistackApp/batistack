<?php

namespace Database\Factories\Banque;

use App\Models\Banque\BankAccount;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'bank_name' => $this->faker->name(),
            'iban' => $this->faker->word(),
            'bic' => $this->faker->word(),
            'currency' => $this->faker->word(),
            'type' => $this->faker->word(),
            'current_balance' => $this->faker->randomFloat(),
            'last_synced_at' => Carbon::now(),
            'bridge_item_id' => $this->faker->word(),
            'bridge_account_id' => $this->faker->word(),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
        ];
    }
}

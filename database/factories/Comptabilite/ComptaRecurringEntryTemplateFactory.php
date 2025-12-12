<?php

namespace Database\Factories\Comptabilite;

use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Comptabilite\ComptaRecurringEntryTemplate;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ComptaRecurringEntryTemplateFactory extends Factory
{
    protected $model = ComptaRecurringEntryTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'label' => $this->faker->word(),
            'amount' => $this->faker->randomFloat(),
            'periodicity' => $this->faker->word(),
            'last_posting_date' => Carbon::now(),
            'next_posting_date' => Carbon::now(),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'journal_id' => ComptaJournal::factory(),
            'account_debit_id' => ComptaAccount::factory(),
            'account_credit_id' => ComptaAccount::factory(),
        ];
    }
}

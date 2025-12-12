<?php

namespace Database\Factories\Comptabilite;

use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ComptaEntryFactory extends Factory
{
    protected $model = ComptaEntry::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'reference' => $this->faker->word(),
            'label' => $this->faker->word(),
            'debit' => $this->faker->randomFloat(),
            'credit' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'compta_journal_id' => ComptaJournal::factory(),
            'compta_account_id' => ComptaAccount::factory(),
        ];
    }
}

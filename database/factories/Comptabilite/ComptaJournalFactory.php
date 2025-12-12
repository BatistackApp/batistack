<?php

namespace Database\Factories\Comptabilite;

use App\Models\Comptabilite\ComptaJournal;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ComptaJournalFactory extends Factory
{
    protected $model = ComptaJournal::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->word(),
            'label' => $this->faker->word(),
            'type' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
        ];
    }
}

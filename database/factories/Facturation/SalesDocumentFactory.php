<?php

namespace Database\Factories\Facturation;

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\Facturation\SalesDocument;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SalesDocumentFactory extends Factory
{
    protected $model = SalesDocument::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'reference' => $this->faker->word(),
            'version' => $this->faker->randomNumber(),
            'status' => $this->faker->word(),
            'date' => Carbon::now(),
            'validity_date' => Carbon::now(),
            'due_date' => Carbon::now(),
            'cuurency_code' => $this->faker->word(),
            'total_ht' => $this->faker->randomFloat(),
            'total_vat' => $this->faker->randomFloat(),
            'total_ttc' => $this->faker->randomFloat(),
            'total_cost' => $this->faker->randomFloat(),
            'margin_amount' => $this->faker->randomFloat(),
            'header_note' => $this->faker->word(),
            'footer_note' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'tiers_id' => Tiers::factory(),
            'chantiers_id' => Chantiers::factory(),
        ];
    }
}

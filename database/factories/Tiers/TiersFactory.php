<?php

namespace Database\Factories\Tiers;

use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TiersFactory extends Factory
{
    protected $model = Tiers::class;

    public function definition()
    {
        return [
            'is_customer' => $this->faker->boolean(),
            'is_supplier' => $this->faker->boolean(),
            'is_subcontractor' => $this->faker->boolean(),
            'nature' => $this->faker->word(),
            'name' => $this->faker->name(),
            'display_name' => $this->faker->name(),
            'vat_number' => $this->faker->word(),
            'siret_number' => $this->faker->word(),
            'naf_number' => $this->faker->word(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->word(),
            'address' => $this->faker->address(),
            'code_postal' => $this->faker->postcode(),
            'ville' => $this->faker->word(),
            'pays' => $this->faker->word(),
            'payment_condition' => $this->faker->word(),
            'outstanding_limit' => $this->faker->randomFloat(),
            'notes' => $this->faker->word(),
            'is_activre' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
        ];
    }
}

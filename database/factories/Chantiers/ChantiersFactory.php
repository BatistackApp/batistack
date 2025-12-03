<?php

namespace Database\Factories\Chantiers;

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ChantiersFactory extends Factory
{
    protected $model = Chantiers::class;

    public function definition()
    {
        return [
            'reference' => $this->faker->word(),
            'name' => $this->faker->name(),
            'status' => $this->faker->word(),
            'address' => $this->faker->address(),
            'code_postal' => $this->faker->postcode(),
            'ville' => $this->faker->word(),
            'pays' => $this->faker->word(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'date_start' => Carbon::now(),
            'end_date_planned' => Carbon::now(),
            'end_date_real' => Carbon::now(),
            'description' => $this->faker->text(),
            'access_instructions' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'tiers_id' => Tiers::factory(),
        ];
    }
}

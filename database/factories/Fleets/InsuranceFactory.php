<?php

namespace Database\Factories\Fleets;

use App\Models\Fleets\Fleet;
use App\Models\Fleets\Insurance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class InsuranceFactory extends Factory
{
    protected $model = Insurance::class;

    public function definition(): array
    {
        return [
            'insurer_name' => $this->faker->name(),
            'contract_number' => $this->faker->word(),
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now(),
            'annual_cost' => $this->faker->randomFloat(),
            'coverage_details' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'fleet_id' => Fleet::factory(),
        ];
    }
}

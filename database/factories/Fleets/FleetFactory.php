<?php

namespace Database\Factories\Fleets;

use App\Models\Core\Company;
use App\Models\Fleets\Fleet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class FleetFactory extends Factory
{
    protected $model = Fleet::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'internal_ref' => $this->faker->word(),
            'type' => $this->faker->word(),
            'registration' => $this->faker->word(),
            'serial_number' => $this->faker->word(),
            'brand' => $this->faker->word(),
            'model' => $this->faker->word(),
            'purchase_date' => Carbon::now(),
            'purchase_price' => $this->faker->randomFloat(),
            'current_value' => $this->faker->randomFloat(),
            'is_available' => $this->faker->boolean(),
            'kilometrage' => $this->faker->randomNumber(),
            'last_check_date' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
        ];
    }
}

<?php

namespace Database\Factories\Fleets;

use App\Models\Fleets\Fleet;
use App\Models\Fleets\Maintenance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    public function definition(): array
    {
        return [
            'date_maintenance' => Carbon::now(),
            'type' => $this->faker->word(),
            'description' => $this->faker->text(),
            'cost' => $this->faker->randomFloat(),
            'invoice_ref' => $this->faker->word(),
            'next_mileage' => $this->faker->word(),
            'next_date' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'fleet_id' => Fleet::factory(),
        ];
    }
}

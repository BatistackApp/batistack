<?php

namespace Database\Factories\Fleets;

use App\Models\Core\Company;
use App\Models\Fleets\Fleet;
use App\Models\Fleets\UlysConsumption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UlysConsumptionFactory extends Factory
{
    protected $model = UlysConsumption::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'fleet_id' => Fleet::factory(),
        ];
    }
}

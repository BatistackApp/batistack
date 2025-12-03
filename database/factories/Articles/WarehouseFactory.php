<?php

namespace Database\Factories\Articles;

use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'is_default' => $this->faker->boolean(),
            'address' => $this->faker->address(),
            'code_postal' => $this->faker->postcode(),
            'ville' => $this->faker->word(),
            'pays' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
        ];
    }
}

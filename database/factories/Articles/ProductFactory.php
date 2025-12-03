<?php

namespace Database\Factories\Articles;

use App\Models\Articles\Product;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'unit' => $this->faker->word(),
            'reference' => $this->faker->word(),
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'supplier_reference' => $this->faker->word(),
            'ean_code' => $this->faker->word(),
            'buying_price' => $this->faker->randomFloat(),
            'selling_price' => $this->faker->randomFloat(),
            'vat_rate' => $this->faker->randomFloat(),
            'is_stockable' => $this->faker->boolean(),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'main_supplier_id' => Tiers::factory(),
        ];
    }
}

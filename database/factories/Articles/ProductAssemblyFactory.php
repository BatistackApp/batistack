<?php

namespace Database\Factories\Articles;

use App\Models\Articles\Product;
use App\Models\Articles\ProductAssembly;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ProductAssemblyFactory extends Factory
{
    protected $model = ProductAssembly::class;

    public function definition(): array
    {
        return [
            'quantity' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'parent_product_id' => Product::factory(),
            'child_product_id' => Product::factory(),
        ];
    }
}

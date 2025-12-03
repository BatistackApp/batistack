<?php

namespace Database\Factories\Articles;

use App\Models\Articles\InventoryStock;
use App\Models\Articles\Product;
use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class InventoryStockFactory extends Factory
{
    protected $model = InventoryStock::class;

    public function definition(): array
    {
        return [
            'quantity_on_hand' => $this->faker->randomFloat(),
            'quantity_reserved' => $this->faker->randomFloat(),
            'quantity_alert' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
        ];
    }
}

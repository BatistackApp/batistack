<?php

namespace Database\Factories\Facturation;

use App\Models\Articles\Product;
use App\Models\Facturation\SalesDocument;
use App\Models\Facturation\SalesDocumentLine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SalesDocumentLineFactory extends Factory
{
    protected $model = SalesDocumentLine::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->word(),
            'sort_order' => $this->faker->randomNumber(),
            'label' => $this->faker->word(),
            'description' => $this->faker->text(),
            'quantity' => $this->faker->randomFloat(),
            'unit' => $this->faker->word(),
            'unit_price' => $this->faker->randomFloat(),
            'vat_rate' => $this->faker->randomFloat(),
            'discount_rate' => $this->faker->randomFloat(),
            'buying_price' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'sales_document_id' => SalesDocument::factory(),
            'product_id' => Product::factory(),
        ];
    }
}

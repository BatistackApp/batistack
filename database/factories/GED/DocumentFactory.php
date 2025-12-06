<?php

namespace Database\Factories\GED;

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\GED\Document;
use App\Models\GED\DocumentFolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'expiration_date' => Carbon::now(),
            'is_valid' => $this->faker->boolean(),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'document_folder_id' => DocumentFolder::factory(),
            'chantiers_id' => Chantiers::factory(),
        ];
    }
}

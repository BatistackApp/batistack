<?php

namespace Database\Factories\GED;

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\GED\DocumentFolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DocumentFolderFactory extends Factory
{
    protected $model = DocumentFolder::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'color' => $this->faker->word(),
            'is_locked' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'chantiers_id' => Chantiers::factory(),
        ];
    }
}

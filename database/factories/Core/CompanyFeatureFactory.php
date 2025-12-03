<?php

namespace Database\Factories\Core;

use App\Models\Core\Company;
use App\Models\Core\CompanyFeature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CompanyFeatureFactory extends Factory
{
    protected $model = CompanyFeature::class;

    public function definition()
    {
        return [
            'feature_code' => $this->faker->word(),
            'value' => $this->faker->word(),
            'expires_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
        ];
    }
}

<?php

namespace Database\Factories\Comptabilite;

use App\Models\Comptabilite\ComptaAccount;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ComptaAccountFactory extends Factory
{
    protected $model = ComptaAccount::class;

    public function definition(): array
    {
        return [
            'number' => $this->faker->word(),
            'label' => $this->faker->word(),
            'is_active' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
        ];
    }
}

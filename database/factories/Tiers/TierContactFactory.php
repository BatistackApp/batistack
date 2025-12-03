<?php

namespace Database\Factories\Tiers;

use App\Models\Core\Company;
use App\Models\Tiers\TierContact;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TierContactFactory extends Factory
{
    protected $model = TierContact::class;

    public function definition()
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'job_title' => $this->faker->word(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'is_primary' => $this->faker->boolean(),
            'receives_billing' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'tiers_id' => Tiers::factory(),
        ];
    }
}

<?php

namespace Database\Factories\RH;

use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'color' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'leader_id' => Employee::factory(),
        ];
    }
}

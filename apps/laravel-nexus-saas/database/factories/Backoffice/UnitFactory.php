<?php

namespace Database\Factories\Backoffice;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Backoffice\Unit;
use App\Models\Backoffice\Company;
use App\Models\Backoffice\Department;
use Illuminate\Support\Str;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'name' => $this->faker->word . ' Unit',
            'code' => strtoupper($this->faker->lexify('UNT-???')),
            'type' => 'project_team',
            'status' => 'active',
        ];
    }
}

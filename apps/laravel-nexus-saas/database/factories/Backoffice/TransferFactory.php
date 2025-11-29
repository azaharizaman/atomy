<?php

namespace Database\Factories\Backoffice;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Backoffice\Transfer;
use App\Models\Backoffice\Staff;
use App\Models\Backoffice\Department;
use Illuminate\Support\Str;

class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'staff_id' => Staff::factory(),
            'from_department_id' => Department::factory(),
            'to_department_id' => Department::factory(),
            'effective_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status' => 'pending',
            'type' => 'permanent',
            'reason' => $this->faker->sentence,
        ];
    }
}

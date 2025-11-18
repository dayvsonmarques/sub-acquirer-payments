<?php

namespace Database\Factories;

use App\Models\Subacquirer;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubacquirerFactory extends Factory
{
    protected $model = Subacquirer::class;

    public function definition(): array
    {
        $name = 'Subadq' . strtoupper(fake()->unique()->bothify('?'));
        $code = strtolower($name);

        return [
            'name' => $name,
            'code' => $code,
            'base_url' => 'https://' . fake()->domainName(),
            'config' => [
                'timeout' => fake()->numberBetween(20, 60),
                'retry_attempts' => fake()->numberBetween(1, 5),
            ],
            'is_active' => fake()->boolean(90),
        ];
    }
}

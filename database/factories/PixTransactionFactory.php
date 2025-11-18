<?php

namespace Database\Factories;

use App\Models\PixTransaction;
use App\Models\User;
use App\Models\Subacquirer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PixTransactionFactory extends Factory
{
    protected $model = PixTransaction::class;

    public function definition(): array
    {
        $statuses = [
            PixTransaction::STATUS_PENDING,
            PixTransaction::STATUS_CONFIRMED,
            PixTransaction::STATUS_FAILED,
            PixTransaction::STATUS_CANCELLED,
        ];

        $pixKeyTypes = ['cpf', 'email', 'phone', 'random'];
        $pixKeyType = fake()->randomElement($pixKeyTypes);

        $pixKey = match ($pixKeyType) {
            'cpf' => $this->generateCpf(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'random' => fake()->uuid(),
            default => fake()->uuid(),
        };

        $status = fake()->randomElement($statuses);
        $confirmedAt = $status === PixTransaction::STATUS_CONFIRMED 
            ? fake()->dateTimeBetween('-30 days', 'now') 
            : null;

        return [
            'user_id' => User::factory(),
            'subacquirer_id' => Subacquirer::factory(),
            'transaction_id' => 'PIX-' . strtoupper(fake()->bothify('########')) . '-' . time(),
            'external_id' => fake()->optional()->bothify('EXT-########'),
            'amount' => fake()->randomFloat(2, 10, 10000),
            'pix_key' => $pixKey,
            'pix_key_type' => $pixKeyType,
            'status' => $status,
            'description' => fake()->optional()->sentence(),
            'request_data' => [
                'transaction_id' => 'PIX-' . strtoupper(fake()->bothify('########')),
                'amount' => fake()->randomFloat(2, 10, 10000),
                'pix_key' => $pixKey,
                'pix_key_type' => $pixKeyType,
            ],
            'response_data' => [
                'success' => $status !== PixTransaction::STATUS_FAILED,
                'id' => fake()->optional()->bothify('EXT-########'),
            ],
            'webhook_data' => $status === PixTransaction::STATUS_CONFIRMED ? [
                'transaction_id' => fake()->bothify('EXT-########'),
                'status' => PixTransaction::STATUS_CONFIRMED,
                'confirmed_at' => $confirmedAt?->format('c'),
            ] : null,
            'confirmed_at' => $confirmedAt,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PixTransaction::STATUS_PENDING,
            'confirmed_at' => null,
            'webhook_data' => null,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PixTransaction::STATUS_CONFIRMED,
            'confirmed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'webhook_data' => [
                'transaction_id' => $attributes['external_id'] ?? $attributes['transaction_id'],
                'status' => PixTransaction::STATUS_CONFIRMED,
                'confirmed_at' => now()->format('c'),
            ],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PixTransaction::STATUS_FAILED,
            'confirmed_at' => null,
            'webhook_data' => null,
        ]);
    }

    private function generateCpf(): string
    {
        $n1 = fake()->numberBetween(0, 9);
        $n2 = fake()->numberBetween(0, 9);
        $n3 = fake()->numberBetween(0, 9);
        $n4 = fake()->numberBetween(0, 9);
        $n5 = fake()->numberBetween(0, 9);
        $n6 = fake()->numberBetween(0, 9);
        $n7 = fake()->numberBetween(0, 9);
        $n8 = fake()->numberBetween(0, 9);
        $n9 = fake()->numberBetween(0, 9);
        
        $d1 = $n9 * 2 + $n8 * 3 + $n7 * 4 + $n6 * 5 + $n5 * 6 + $n4 * 7 + $n3 * 8 + $n2 * 9 + $n1 * 10;
        $d1 = 11 - ($d1 % 11);
        if ($d1 >= 10) $d1 = 0;
        
        $d2 = $d1 * 2 + $n9 * 3 + $n8 * 4 + $n7 * 5 + $n6 * 6 + $n5 * 7 + $n4 * 8 + $n3 * 9 + $n2 * 10 + $n1 * 11;
        $d2 = 11 - ($d2 % 11);
        if ($d2 >= 10) $d2 = 0;
        
        return sprintf('%d%d%d%d%d%d%d%d%d%d%d', $n1, $n2, $n3, $n4, $n5, $n6, $n7, $n8, $n9, $d1, $d2);
    }
}

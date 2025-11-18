<?php

namespace Database\Seeders;

use App\Models\PixTransaction;
use App\Models\Subacquirer;
use App\Models\User;
use App\Models\WithdrawTransaction;
use Illuminate\Database\Seeder;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        $subacquirers = Subacquirer::whereIn('code', ['subadqa', 'subadqb'])->get();

        if ($subacquirers->count() < 2) {
            throw new \Exception('SubadqA e SubadqB devem existir antes de executar este seeder. Execute o SubacquirerSeeder primeiro.');
        }

        $users = User::factory()
            ->count(20)
            ->create()
            ->each(function ($user) use ($subacquirers) {
                $user->update([
                    'subacquirer_id' => $subacquirers->random()->id,
                ]);
            });

        foreach ($users as $user) {
            PixTransaction::factory()
                ->count(fake()->numberBetween(5, 15))
                ->create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $user->subacquirer_id,
                ]);

            WithdrawTransaction::factory()
                ->count(fake()->numberBetween(3, 10))
                ->create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $user->subacquirer_id,
                ]);
        }

        $this->command->info('Fake data generated successfully!');
        $this->command->info('Users: ' . User::count());
        $this->command->info('Subacquirers: ' . Subacquirer::count());
        $this->command->info('PIX Transactions: ' . PixTransaction::count());
        $this->command->info('Withdraw Transactions: ' . WithdrawTransaction::count());
    }
}

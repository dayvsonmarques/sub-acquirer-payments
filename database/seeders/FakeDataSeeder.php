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

        $users = User::whereNotNull('subacquirer_id')->get();

        if ($users->count() < 3) {
            throw new \Exception('É necessário ter pelo menos 3 usuários com subadquirente. Execute o DatabaseSeeder primeiro.');
        }

        $clients = $users->take(3);

        foreach ($clients as $user) {
            $existingPixCount = PixTransaction::where('user_id', $user->id)->count();
            $existingWithdrawCount = WithdrawTransaction::where('user_id', $user->id)->count();
            
            $pixToCreate = max(0, 3 - $existingPixCount);
            $withdrawToCreate = max(0, 3 - $existingWithdrawCount);
            
            if ($pixToCreate > 0) {
                PixTransaction::factory()->count($pixToCreate)->create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $user->subacquirer_id,
                ]);
            }
            
            if ($withdrawToCreate > 0) {
                WithdrawTransaction::factory()->count($withdrawToCreate)->create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $user->subacquirer_id,
                ]);
            }
        }

        $this->command->info('Fake data generated successfully!');
        $this->command->info('Users: ' . User::count());
        $this->command->info('Subacquirers: ' . Subacquirer::count());
        $this->command->info('PIX Transactions: ' . PixTransaction::count());
        $this->command->info('Withdraw Transactions: ' . WithdrawTransaction::count());
    }
}

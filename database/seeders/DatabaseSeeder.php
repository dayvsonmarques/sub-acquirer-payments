<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SubacquirerSeeder::class,
        ]);

        // User::factory(10)->create();

        $subacquirerA = \App\Models\Subacquirer::where('code', 'subadqa')->first();
        $subacquirerB = \App\Models\Subacquirer::where('code', 'subadqb')->first();

        User::factory()->create([
            'name' => 'Test User A',
            'email' => 'testa@example.com',
            'subacquirer_id' => $subacquirerA?->id,
        ]);

        User::factory()->create([
            'name' => 'Test User B',
            'email' => 'testb@example.com',
            'subacquirer_id' => $subacquirerB?->id,
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@super.com',
            'password' => Hash::make('Admin@123'),
            'subacquirer_id' => null,
        ]);
    }
}

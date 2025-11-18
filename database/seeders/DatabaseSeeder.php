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

        User::updateOrCreate(
            ['email' => 'clientea@example.com'],
            [
                'name' => 'Cliente A',
                'subacquirer_id' => $subacquirerA?->id,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'clienteb@example.com'],
            [
                'name' => 'Cliente B',
                'subacquirer_id' => $subacquirerB?->id,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'clientec@example.com'],
            [
                'name' => 'Cliente C',
                'subacquirer_id' => $subacquirerA?->id,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@super.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Admin@123'),
                'subacquirer_id' => null,
            ]
        );
    }
}

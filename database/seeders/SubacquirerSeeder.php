<?php

namespace Database\Seeders;

use App\Models\Subacquirer;
use Illuminate\Database\Seeder;

class SubacquirerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subacquirer::updateOrCreate(
            ['code' => 'subadqa'],
            [
                'name' => 'SubadqA',
                'base_url' => 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io',
                'is_active' => true,
                'config' => [
                    'timeout' => 30,
                    'retry_attempts' => 3,
                ],
            ]
        );

        Subacquirer::updateOrCreate(
            ['code' => 'subadqb'],
            [
                'name' => 'SubadqB',
                'base_url' => 'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io',
                'is_active' => true,
                'config' => [
                    'timeout' => 30,
                    'retry_attempts' => 3,
                ],
            ]
        );
    }
}

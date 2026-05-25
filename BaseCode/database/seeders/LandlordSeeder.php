<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LandlordSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'landlord@test.com'],
            [
                'name'     => 'Nguyễn Văn Chủ',
                'email'    => 'landlord@test.com',
                'password' => Hash::make('password'),
                'role'     => 'landlord',
            ]
        );
    }
}

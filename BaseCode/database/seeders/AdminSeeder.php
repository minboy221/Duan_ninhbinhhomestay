<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo tài khoản Admin nếu chưa tồn tại
        User::updateOrCreate(
            ['email' => 'admin@ninhbinhhomestay.vn'],
            [
                'name'              => 'Admin',
                'email'             => 'admin@ninhbinhhomestay.vn',
                'password'          => Hash::make('Admin@123'),
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Tài khoản admin đã được tạo:');
        $this->command->info('   Email   : admin@ninhbinhhomestay.vn');
        $this->command->info('   Password: Admin@123');
    }
}

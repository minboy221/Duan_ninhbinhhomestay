<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //tài khoản cho Admin
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'], // Kiểm tra nếu mail này chưa có thì mới tạo
            [
                'name' => 'System Admin',
                'password' => Hash::make('12345678'), // Mật khẩu mặc định
                'role' => 'admin', // Gán role dạng chuỗi
                'email_verified_at' => now(),
            ]
        );

    }
}

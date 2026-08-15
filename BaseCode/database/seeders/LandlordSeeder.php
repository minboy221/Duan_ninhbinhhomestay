<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LandlordSeeder extends Seeder
{
    public function run(): void
    {
        $landlord = User::updateOrCreate(
            ['email' => 'landlord@test.com'],
            [
                'name'     => 'Nguyễn Văn Chủ',
                'email'    => 'landlord@test.com',
                'password' => Hash::make('password'),
                'role'     => 'landlord',
                'phone'    => '0912345678',
                'cccd_number' => '037123456789',
                'is_verified' => true,
                'bank_name' => 'Vietcombank',
                'bank_account_no' => '1234567890',
                'bank_account_name' => 'NGUYEN VAN CHU',
            ]
        );

        $newId = $landlord->id;
        $oldId = 6;

        // Copy private files to match this landlord
        $filesToCopy = [
            'private/kyc/id_cards/user_' . $oldId . '_cccd_truoc_1780282532.jpg' => 'private/kyc/id_cards/user_' . $newId . '_cccd_truoc_1780282532.jpg',
            'private/kyc/id_cards/user_' . $oldId . '_cccd_sau_1780282532.jpg' => 'private/kyc/id_cards/user_' . $newId . '_cccd_sau_1780282532.jpg',
            'private/kyc/faces/user_' . $oldId . '_khuon_mat_1780282532.jpg' => 'private/kyc/faces/user_' . $newId . '_khuon_mat_1780282532.jpg',
            'private/properties/contracts/user_' . $oldId . '_hop_dong_0_1780282532.png' => 'private/properties/contracts/user_' . $newId . '_hop_dong_0_1780282532.png',
            'private/properties/rooms/user_' . $oldId . '_phong_tro_0_1780282532.png' => 'private/properties/rooms/user_' . $newId . '_phong_tro_0_1780282532.png',
        ];

        foreach ($filesToCopy as $src => $dest) {
            if ($src !== $dest && \Illuminate\Support\Facades\Storage::disk('local')->exists($src)) {
                \Illuminate\Support\Facades\Storage::disk('local')->copy($src, $dest);
            }
        }

        // Tạo dữ liệu kyc
        DB::table('user_verifications')->updateOrInsert(
            ['user_id' => $newId],
            [
                'id_card_number' => '037123456789',
                'id_card_front' => 'private/kyc/id_cards/user_' . $newId . '_cccd_truoc_1780282532.jpg',
                'id_card_back' => 'private/kyc/id_cards/user_' . $newId . '_cccd_sau_1780282532.jpg',
                'face_auth_image' => 'private/kyc/faces/user_' . $newId . '_khuon_mat_1780282532.jpg',
                'kyc_status' => 'approved',
                'kyc_notes' => 'Hồ sơ đầy đủ, hợp lệ',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        // Tạo nhà trọ cho landlord@test.com nếu chưa có
        DB::table('boarding_houses')->updateOrInsert(
            ['user_id' => $newId],
            [
                'name' => 'Nhà trọ Nguyễn Văn Chủ',
                'district' => 'Ninh Khánh',
                'address_detail' => '456 Đường Lê Hồng Phong',
                'status' => 'approved',
                'contract_images' => json_encode(['private/properties/contracts/user_' . $newId . '_hop_dong_0_1780282532.png']),
                'room_images' => json_encode(['private/properties/rooms/user_' . $newId . '_phong_tro_0_1780282532.png']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );
    }
}

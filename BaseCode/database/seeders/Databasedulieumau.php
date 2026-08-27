<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class Databasedulieumau extends Seeder
{
    public function run()
    {
        // No truncates here to preserve preceding seeders' data during migrate:fresh --seed

        // 1. Tạo Users
        $existingAdmin = DB::table('users')->where('email', 'admin@staywork.com')->first();
        if ($existingAdmin) {
            $adminId = $existingAdmin->id;
        } else {
            $adminId = DB::table('users')->insertGetId([
                'name' => 'Admin Ninh Binh StayWork',
                'email' => 'admin@staywork.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $existingLandlord = DB::table('users')->where('email', 'chutro@staywork.com')->first();
        if ($existingLandlord) {
            $landlordId = $existingLandlord->id;
        } else {
            $landlordId = DB::table('users')->insertGetId([
                'name' => 'Nguyễn Văn Chủ Trọ',
                'email' => 'chutro@staywork.com',
                'password' => Hash::make('password123'),
                'phone' => '0912345678',
                'cccd_number' => '037123456789',
                'role' => 'landlord',
                'is_verified' => true,
                'bank_name' => 'Vietcombank',
                'bank_account_no' => '1234567890',
                'bank_account_name' => 'NGUYEN VAN CHU TRO',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $existingTenant = DB::table('users')->where('email', 'nguoithue@staywork.com')->first();
        if ($existingTenant) {
            $tenantId = $existingTenant->id;
        } else {
            $tenantId = DB::table('users')->insertGetId([
                'name' => 'Trần Thị Người Thuê',
                'email' => 'nguoithue@staywork.com',
                'password' => Hash::make('password123'),
                'phone' => '0987654321',
                'cccd_number' => '037987654321',
                'role' => 'tenant',
                'is_verified' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Copy private files to match the new landlord user ID dynamically
        $oldId = 6;
        $newId = $landlordId;
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

        // Tạo dữ liệu kyc cho chủ trọ
        DB::table('user_verifications')->updateOrInsert(
            ['user_id' => $landlordId],
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

        // 2. Tạo Boarding House (Nhà trọ)
        $existingBH = DB::table('boarding_houses')->where('user_id', $landlordId)->where('name', 'Nhà trọ Hoa Lư View')->first();
        if ($existingBH) {
            $boardingHouseId = $existingBH->id;
        } else {
            $boardingHouseId = DB::table('boarding_houses')->insertGetId([
                'user_id' => $landlordId,
                'name' => 'Nhà trọ Hoa Lư View',
                'district' => 'Hoa Lư',
                'address_detail' => '123 Đường Tràng An',
                'status' => 'approved',
                'contract_images' => json_encode(['private/properties/contracts/user_' . $landlordId . '_hop_dong_0_1780282532.png']),
                'room_images' => json_encode(['private/properties/rooms/user_' . $landlordId . '_phong_tro_0_1780282532.png']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 3. Tạo Property (Khu trọ / Homestay phục vụ tìm kiếm)
        $existingProp = DB::table('properties')->where('landlord_id', $landlordId)->where('name', 'Homestay Hoa Lư View')->first();
        if ($existingProp) {
            $propertyId = $existingProp->id;
        } else {
            $propertyId = DB::table('properties')->insertGetId([
                'landlord_id' => $landlordId,
                'name' => 'Homestay Hoa Lư View',
                'description' => 'Homestay thoáng mát gần trung tâm, đầy đủ tiện nghi.',
                'address' => '123 Đường Tràng An',
                'city' => 'Ninh Bình',
                'type' => 'homestay',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 4. Tạo Floor (Tầng)
        $existingFloor = DB::table('floors')->where('property_id', $propertyId)->where('name', 'Tầng 1')->first();
        if ($existingFloor) {
            $floorId = $existingFloor->id;
        } else {
            $floorId = DB::table('floors')->insertGetId([
                'property_id' => $propertyId,
                'name' => 'Tầng 1',
                'address' => '123 Đường Tràng An',
                'sort_order' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 5. Tạo Room (Phòng)
        $existingRoom = DB::table('rooms')->where('boarding_house_id', $boardingHouseId)->where('room_number', 'P101')->first();
        if ($existingRoom) {
            $roomId = $existingRoom->id;
        } else {
            $roomId = DB::table('rooms')->insertGetId([
                'boarding_house_id' => $boardingHouseId,
                'floor_id' => $floorId,
                'room_number' => 'P101',
                'address' => '123 Đường Tràng An, Tầng 1',
                'price' => 2500000.00,
                'area' => 25.5,
                'capacity' => 2,
                'status' => 'rented',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 6. Tạo Contract (Hợp đồng mẫu)
        $existingContract = DB::table('contracts')->where('tenant_id', $tenantId)->where('room_id', $roomId)->first();
        if ($existingContract) {
            $contractId = $existingContract->id;
        } else {
            $contractId = DB::table('contracts')->insertGetId([
                'tenant_id' => $tenantId,
                'room_id' => $roomId,
                'start_date' => Carbon::now()->format('Y-m-d'),
                'end_date' => Carbon::now()->addMonths(6)->format('Y-m-d'),
                'deposit_amount' => 2500000.00,
                'status' => 'signed',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 7. Tạo danh mục dịch vụ mẫu cho khu trọ
        $existingServiceDien = DB::table('services')->where('property_id', $propertyId)->where('name', 'Tiền Điện')->first();
        if ($existingServiceDien) {
            $serviceDienId = $existingServiceDien->id;
        } else {
            $serviceDienId = DB::table('services')->insertGetId([
                'property_id' => $propertyId,
                'name' => 'Tiền Điện',
                'price' => 3500.00,
                'type' => 'per_kwh',
                'description' => 'Tính theo số ký điện tiêu thụ',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $existingServiceNuoc = DB::table('services')->where('property_id', $propertyId)->where('name', 'Tiền Nước')->first();
        if ($existingServiceNuoc) {
            $serviceNuocId = $existingServiceNuoc->id;
        } else {
            $serviceNuocId = DB::table('services')->insertGetId([
                'property_id' => $propertyId,
                'name' => 'Tiền Nước',
                'price' => 20000.00,
                'type' => 'per_m3',
                'description' => 'Tính theo khối nước tiêu thụ',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $existingServiceWifi = DB::table('services')->where('property_id', $propertyId)->where('name', 'Internet Wifi')->first();
        if ($existingServiceWifi) {
            $serviceWifiId = $existingServiceWifi->id;
        } else {
            $serviceWifiId = DB::table('services')->insertGetId([
                'property_id' => $propertyId,
                'name' => 'Internet Wifi',
                'price' => 50000.00,
                'type' => 'fixed',
                'description' => 'Phí cố định theo phòng hàng tháng',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 8. Tạo Hóa đơn mẫu
        $invoiceCode = 'HD-' . date('Ym') . '-001';
        $existingInvoice = DB::table('invoices')->where('invoice_code', $invoiceCode)->first();
        if ($existingInvoice) {
            $invoiceId = $existingInvoice->id;
        } else {
            $invoiceId = DB::table('invoices')->insertGetId([
                'contract_id' => $contractId,
                'invoice_code' => $invoiceCode,
                'billing_month' => date('Y-m'),
                'total_amount' => 2725000.00,
                'status' => 'unpaid',
                'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 9. Tạo Chi tiết hóa đơn mẫu
        if (!DB::table('invoice_details')->where('invoice_id', $invoiceId)->exists()) {
            DB::table('invoice_details')->insert([
                [
                    'invoice_id' => $invoiceId,
                    'service_id' => null,
                    'item_name' => 'Tiền thuê nhà tháng này',
                    'old_index' => null,
                    'new_index' => null,
                    'meter_image_path' => null,
                    'quantity' => 1,
                    'price' => 2500000.00,
                    'subtotal' => 2500000.00,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'invoice_id' => $invoiceId,
                    'service_id' => $serviceDienId,
                    'item_name' => 'Tiền Điện',
                    'old_index' => 1200,
                    'new_index' => 1250,
                    'meter_image_path' => 'uploads/meters/dien_thang5_2026.jpg',
                    'quantity' => 50,
                    'price' => 3500.00,
                    'subtotal' => 175000.00,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'invoice_id' => $invoiceId,
                    'service_id' => $serviceWifiId,
                    'item_name' => 'Internet Wifi',
                    'old_index' => null,
                    'new_index' => null,
                    'meter_image_path' => null,
                    'quantity' => 1,
                    'price' => 50000.00,
                    'subtotal' => 50000.00,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ]);
        }

        // 10. Tạo dữ liệu mẫu cho Yêu cầu sửa chữa
        if (!DB::table('maintenance_requests')->where('room_id', $roomId)->where('tenant_id', $tenantId)->exists()) {
            DB::table('maintenance_requests')->insert([
                'room_id' => $roomId,
                'tenant_id' => $tenantId,
                'title' => 'Hỏng vòi hoa sen',
                'description' => 'Vòi hoa sen trong nhà tắm bị rỉ nước mạnh, nhờ chủ nhà qua sửa giúp.',
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 11. Tạo dữ liệu mẫu cho Đánh giá homestay
        if (!DB::table('reviews')->where('property_id', $propertyId)->where('tenant_id', $tenantId)->exists()) {
            DB::table('reviews')->insert([
                'property_id' => $propertyId,
                'tenant_id' => $tenantId,
                'rating' => 5,
                'comment' => 'Phòng sạch sẽ, chủ nhà thân thiện, thủ tục ký hợp đồng online rất nhanh gọn.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // 12. Tạo bài đăng mẫu (RoomPost)
        if (!DB::table('room_posts')->where('landlord_id', $landlordId)->where('room_id', $roomId)->exists()) {
            DB::table('room_posts')->insert([
                'landlord_id' => $landlordId,
                'room_id' => $roomId,
                'title' => 'Phòng trọ P101 đầy đủ tiện nghi, thoáng mát sạch sẽ',
                'description' => 'Phòng trọ khép kín, an ninh tốt, gần trung tâm du lịch Tràng An, phù hợp cho học sinh, sinh viên và người đi làm.',
                'image' => json_encode(['private/properties/rooms/user_' . $landlordId . '_phong_tro_0_1780282532.png']),
                'status' => 'approved',
                'view_count' => 12,
                'is_vip' => false,
                'published_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
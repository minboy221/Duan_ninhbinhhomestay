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
        $adminId = DB::table('users')->insertGetId([
            'name' => 'Admin Ninh Binh StayWork',
            'email' => 'admin@staywork.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

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
        DB::table('user_verifications')->insert([
            'user_id' => $landlordId,
            'id_card_number' => '037123456789',
            'id_card_front' => 'private/kyc/id_cards/user_' . $newId . '_cccd_truoc_1780282532.jpg',
            'id_card_back' => 'private/kyc/id_cards/user_' . $newId . '_cccd_sau_1780282532.jpg',
            'face_auth_image' => 'private/kyc/faces/user_' . $newId . '_khuon_mat_1780282532.jpg',
            'kyc_status' => 'approved',
            'kyc_notes' => 'Hồ sơ đầy đủ, hợp lệ',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 2. Tạo Boarding House (Nhà trọ)
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

        // 3. Tạo Property (Khu trọ / Homestay phục vụ tìm kiếm)
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

        // 4. Tạo Floor (Tầng)
        $floorId = DB::table('floors')->insertGetId([
            'property_id' => $propertyId,
            'name' => 'Tầng 1',
            'address' => '123 Đường Tràng An',
            'sort_order' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 5. Tạo Room (Phòng)
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

        // 6. Tạo Contract (Hợp đồng mẫu)
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

        // 7. Tạo danh mục dịch vụ mẫu cho khu trọ
        $serviceDienId = DB::table('services')->insertGetId([
            'property_id' => $propertyId,
            'name' => 'Tiền Điện',
            'price' => 3500.00,
            'type' => 'per_kwh',
            'description' => 'Tính theo số ký điện tiêu thụ',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $serviceNuocId = DB::table('services')->insertGetId([
            'property_id' => $propertyId,
            'name' => 'Tiền Nước',
            'price' => 20000.00,
            'type' => 'per_m3',
            'description' => 'Tính theo khối nước tiêu thụ',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $serviceWifiId = DB::table('services')->insertGetId([
            'property_id' => $propertyId,
            'name' => 'Internet Wifi',
            'price' => 50000.00,
            'type' => 'fixed',
            'description' => 'Phí cố định theo phòng hàng tháng',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 8. Tạo Hóa đơn mẫu
        $invoiceId = DB::table('invoices')->insertGetId([
            'contract_id' => $contractId,
            'invoice_code' => 'HD-' . date('Ym') . '-001',
            'billing_month' => date('Y-m'),
            'total_amount' => 2725000.00,
            'status' => 'unpaid',
            'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 9. Tạo Chi tiết hóa đơn mẫu
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

        // 10. Tạo dữ liệu mẫu cho Yêu cầu sửa chữa
        DB::table('maintenance_requests')->insert([
            'room_id' => $roomId,
            'tenant_id' => $tenantId,
            'title' => 'Hỏng vòi hoa sen',
            'description' => 'Vòi hoa sen trong nhà tắm bị rỉ nước mạnh, nhờ chủ nhà qua sửa giúp.',
            'status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 11. Tạo dữ liệu mẫu cho Đánh giá homestay
        DB::table('reviews')->insert([
            'property_id' => $propertyId,
            'tenant_id' => $tenantId,
            'rating' => 5,
            'comment' => 'Phòng sạch sẽ, chủ nhà thân thiện, thủ tục ký hợp đồng online rất nhanh gọn.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 12. Tạo bài đăng mẫu (RoomPost)
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
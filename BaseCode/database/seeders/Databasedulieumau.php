<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class Databasedulieumau extends Seeder
{
    public function run()
    {
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

        // 2. Tạo Property (Khu trọ / Homestay)
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

        // 3. Tạo Rooms
        $roomId = DB::table('rooms')->insertGetId([
            'property_id' => $propertyId,
            'room_number' => 'P101',
            'price' => 2500000.00,
            'area' => 25.5,
            'capacity' => 2,
            'status' => 'available',
            'amenities' => 'Điều hòa, Nóng lạnh, Giường tủ',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 4. Tạo Contract (Hợp đồng mẫu)
        $contractId = DB::table('contracts')->insertGetId([
            'tenant_id' => $tenantId,
            'room_id' => $roomId,
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'deposit_amount' => 2500000.00,
            'status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 5. Tạo danh mục dịch vụ mẫu cho khu trọ (sử dụng biến $propertyId từ phần trước)
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

        // 6. Tạo Hóa đơn mẫu (sử dụng biến $contractId từ phần trước)
        $invoiceId = DB::table('invoices')->insertGetId([
            'contract_id' => $contractId,
            'invoice_code' => 'HD-' . date('Ym') . '-001',
            'billing_month' => date('Y-m'),
            'total_amount' => 2725000.00, // Tổng cộng các khoản bên dưới
            'status' => 'unpaid',
            'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 8. Tạo dữ liệu mẫu cho Yêu cầu sửa chữa (sử dụng $roomId và $tenantId từ phần trước)
        DB::table('maintenance_requests')->insert([
            'room_id' => $roomId,
            'tenant_id' => $tenantId, // ID người thuê mẫu
            'title' => 'Hỏng vòi hoa sen',
            'description' => 'Vòi hoa sen trong nhà tắm bị rỉ nước mạnh, nhờ chủ nhà qua sửa giúp.',
            'status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 9. Tạo dữ liệu mẫu cho Đánh giá homestay
        DB::table('reviews')->insert([
            'property_id' => $propertyId,
            'tenant_id' => $tenantId,
            'rating' => 5,
            'comment' => 'Phòng sạch sẽ, chủ nhà thân thiện, thủ tục ký hợp đồng online rất nhanh gọn.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Cập nhật Chi tiết hóa đơn mẫu có kèm ảnh minh chứng công tơ
        DB::table('invoice_details')->insert([
            [
                'invoice_id' => $invoiceId,
                'service_id' => null,
                'item_name' => 'Tiền thuê nhà tháng này',
                'old_index' => null,
                'new_index' => null,
                'meter_image_path' => null, // Tiền phòng không cần ảnh
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
                'meter_image_path' => 'uploads/meters/dien_thang5_2026.jpg', // Đường dẫn ảnh mẫu
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
                'meter_image_path' => null, // Phí cố định không cần ảnh
                'quantity' => 1,
                'price' => 50000.00,
                'subtotal' => 50000.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
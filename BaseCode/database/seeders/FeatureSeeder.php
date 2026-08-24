<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'name' => 'Số phòng tối đa',
                'feature_code' => 'max_rooms',
                'description' => 'Số lượng phòng trọ tối đa chủ trọ được quản lý',
            ],
            [
                'name' => 'Số dãy/tòa nhà tối đa',
                'feature_code' => 'max_properties',
                'description' => 'Số tòa nhà/dãy trọ tối đa có thể khởi tạo',
            ],
            [
                'name' => 'Huy hiệu Chủ trọ Uy tín VIP',
                'feature_code' => 'vip_badge',
                'description' => 'Hiển thị huy hiệu VIP nổi bật trên các bài đăng tìm phòng',
            ],
            [
                'name' => 'Đẩy tin bài đăng',
                'feature_code' => 'priority_listing',
                'description' => 'Số lượt đẩy bài tin đăng lên đầu trang tìm kiếm mỗi tháng',
            ],
            [
                'name' => 'Xuất báo cáo PDF & Excel',
                'feature_code' => 'export_reports',
                'description' => 'Cho phép xuất các file hợp đồng, hóa đơn và báo cáo doanh thu',
            ],
            [
                'name' => 'Số cơ sở tối đa',
                'feature_code' => 'max_boarding_houses',
                'description' => 'Số lượng cơ sở trọ tối đa',
            ],
            [
                'name' => 'Số tin đăng tối đa',
                'feature_code' => 'max_listings',
                'description' => 'Số lượng bài đăng tìm khách tối đa',
            ],
            [
                'name' => 'Chốt điện nước & Hóa đơn',
                'feature_code' => 'manage_invoices',
                'description' => 'Cho phép tính tiền & xuất hóa đơn hàng tháng',
            ],
            [
                'name' => 'Lập Hợp đồng & Đăng ký',
                'feature_code' => 'manage_contracts',
                'description' => 'Cho phép lập hợp đồng & xuất PDF',
            ],
            [
                'name' => 'Duyệt người ở ghép',
                'feature_code' => 'manage_roommates',
                'description' => 'Duyệt yêu cầu ở ghép của khách',
            ],
            [
                'name' => 'Xử lý khiếu nại',
                'feature_code' => 'manage_reports',
                'description' => 'Tiếp nhận phản hồi từ cư dân',
            ],
            [
                'name' => 'Phân quyền quản lý/nhân viên',
                'feature_code' => 'manage_managers',
                'description' => 'Tạo tài khoản phụ cho nhân viên quản lý',
            ],
            [
                'name' => 'Khung viền Avatar VIP lấp lánh',
                'feature_code' => 'avatar_frame',
                'description' => 'Đặc quyền viền vàng VIP lấp lánh trên Avatar',
            ],
        ];

        foreach ($features as $feat) {
            Feature::updateOrCreate(
                ['feature_code' => $feat['feature_code']],
                $feat
            );
        }
    }
}

<?php

namespace Database\Seeders;
use App\Models\Feature;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'name' => 'Số cơ sở tối đa',
                'feature_code' => 'max_boarding_houses',
                'description' => 'số lượng cơ sở trọ tối đa'
            ],
            [
                'name' => 'số phòng tối đa',
                'feature_code' => 'max_rooms',
                'description' => 'số lượng phòng trọ tối đa'
            ],
            [
                'name' => 'Số tin đăng tối đa',
                'feature_code' => 'max_listings',
                'description' => 'Số lượng bài đăng tìm khách'
            ],
            [
                'name' => 'Chốt điện nước & hoá đơn',
                'feature_code' => 'max_listings',
                'description' => 'số lượng bài đăng tìm khách'
            ],
             ['name' => 'Chốt điện nước & Hóa đơn', 'feature_code' => 'manage_invoices', 'description' => 'Cho phép tính tiền & xuất hóa đơn hàng tháng'],
            ['name' => 'Lập Hợp đồng & Đăng ký', 'feature_code' => 'manage_contracts', 'description' => 'Cho phép lập hợp đồng & xuất PDF'],
            ['name' => 'Duyệt người ở ghép', 'feature_code' => 'manage_roommates', 'description' => 'Duyệt yêu cầu ở ghép của khách'],
            ['name' => 'Xử lý khiếu nại', 'feature_code' => 'manage_reports', 'description' => 'Tiếp nhận phản hồi từ cư dân'],
            ['name' => 'Phân quyền quản lý/nhân viên', 'feature_code' => 'manage_managers', 'description' => 'Tạo tài khoản phụ cho nhân viên quản lý'],
            ['name' => 'Khung viền Avatar VIP lấp lánh', 'feature_code' => 'avatar_frame', 'description' => 'Đặc quyền viền vàng VIP lấp lánh trên Avatar'],
        ];
        foreach($features as $feat){
            Feature::updateOrCreate(['feature_code' => $feat['feature_code']],
            $feat);
        }
    }
}

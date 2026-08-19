<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo danh sách Features
        $features = [
            [
                'name' => 'Số phòng trọ tối đa',
                'feature_code' => 'max_rooms',
                'description' => 'Số lượng phòng trọ tối đa chủ trọ được quản lý',
            ],
            [
                'name' => 'Số dãy/tòa nhà tối đa',
                'feature_code' => 'max_properties',
                'description' => 'Số tòa nhà trọ tối đa có thể khởi tạo',
            ],
            [
                'name' => 'Huy hiệu Chủ trọ Uy tín VIP',
                'feature_code' => 'vip_badge',
                'description' => 'Hiển thị huy hiệu VIP nổi bật trên các bài đăng tìm phòng',
            ],
            [
                'name' => 'Số tin đăng tối đa',
                'feature_code' => 'max_listings',
                'description' => 'Số lượng bài đăng tin tìm khách công khai',
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
        ];

        $featureModels = [];
        foreach ($features as $f) {
            $featureModels[$f['feature_code']] = Feature::updateOrCreate(
                ['feature_code' => $f['feature_code']],
                $f
            );
        }

        // 2. Tạo Gói Miễn Phí 2 Tháng (Trial 60 ngày)
        $trialPlan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Gói Dùng Thử (Free 60 ngày)'],
            [
                'price' => 0,
                'duration_days' => 60,
                'badge' => 'Miễn phí 2 tháng',
                'sort_order' => 1,
                'description' => 'Dành cho chủ trọ mới gia nhập hệ thống. Trải nghiệm đầy đủ tính năng trong 60 ngày.',
                'is_active' => true,
            ]
        );
        $trialPlan->features()->sync([
            $featureModels['max_rooms']->id => ['feature_value' => '15'],
            $featureModels['max_properties']->id => ['feature_value' => '1'],
            $featureModels['vip_badge']->id => ['feature_value' => 'false'],
            $featureModels['priority_listing']->id => ['feature_value' => '2'],
            $featureModels['export_reports']->id => ['feature_value' => 'true'],
        ]);

        // 3. Tạo Gói Cơ Bản (Basic)
        $basicPlan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Gói Cơ Bản'],
            [
                'price' => 199000,
                'duration_days' => 30,
                'badge' => 'Tiết kiệm',
                'sort_order' => 2,
                'description' => 'Phù hợp với chủ trọ quy mô vừa và nhỏ (dưới 30 phòng).',
                'is_active' => true,
            ]
        );
        $basicPlan->features()->sync([
            $featureModels['max_rooms']->id => ['feature_value' => '30'],
            $featureModels['max_properties']->id => ['feature_value' => '2'],
            $featureModels['vip_badge']->id => ['feature_value' => 'false'],
            $featureModels['priority_listing']->id => ['feature_value' => '5'],
            $featureModels['export_reports']->id => ['feature_value' => 'true'],
        ]);

        // 4. Tạo Gói Chuyên Nghiệp (Pro)
        $proPlan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Gói Chuyên Nghiệp (Pro)'],
            [
                'price' => 499000,
                'duration_days' => 30,
                'badge' => 'Khuyên dùng',
                'sort_order' => 3,
                'description' => 'Dành cho các chuỗi nhà trọ lớn cần huy hiệu VIP và tính năng cao cấp.',
                'is_active' => true,
            ]
        );
        $proPlan->features()->sync([
            $featureModels['max_rooms']->id => ['feature_value' => '100'],
            $featureModels['max_properties']->id => ['feature_value' => '5'],
            $featureModels['vip_badge']->id => ['feature_value' => 'true'],
            $featureModels['priority_listing']->id => ['feature_value' => '15'],
            $featureModels['export_reports']->id => ['feature_value' => 'true'],
        ]);

        // 5. Tạo Gói VIP Không Giới Hạn
        $vipPlan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Gói VIP Không Giới Hạn'],
            [
                'price' => 1299000,
                'duration_days' => 90,
                'badge' => 'Đặc quyền VIP',
                'sort_order' => 4,
                'description' => 'Không giới hạn số phòng, số tòa nhà và sở hữu các đặc quyền cao nhất.',
                'is_active' => true,
            ]
        );
        $vipPlan->features()->sync([
            $featureModels['max_rooms']->id => ['feature_value' => 'unlimited'],
            $featureModels['max_properties']->id => ['feature_value' => 'unlimited'],
            $featureModels['vip_badge']->id => ['feature_value' => 'true'],
            $featureModels['priority_listing']->id => ['feature_value' => 'unlimited'],
            $featureModels['export_reports']->id => ['feature_value' => 'true'],
        ]);
    }
}

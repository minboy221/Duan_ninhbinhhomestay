<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(FeatureSeeder::class);
        $features = Feature::all()->keyBy('feature_code');

        // Gói 1: Dùng thử 30 ngày (Full VIP)
        $trialPlan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Gói Dùng Thử 30 Ngày (Full VIP)'],
            [
                'price' => 0,
                'duration_days' => 30,
                'badge' => 'DÙNG THỬ VIP',
                'sort_order' => 1,
                'description' => 'Miễn Phí 100% toàn bộ tính năng cao cấp trong 30 ngày đầu tiên.',
                'is_active' => true,
            ]
        );
        $trialPlan->features()->sync([
            $features['max_rooms']->id => ['feature_value' => '-1'],
            $features['max_properties']->id => ['feature_value' => '-1'],
            $features['vip_badge']->id => ['feature_value' => 'true'],
            $features['priority_listing']->id => ['feature_value' => '-1'],
            $features['export_reports']->id => ['feature_value' => 'true'],
            $features['max_boarding_houses']->id => ['feature_value' => '-1'],
            $features['max_listings']->id => ['feature_value' => '-1'],
            $features['manage_invoices']->id => ['feature_value' => 'true'],
            $features['manage_contracts']->id => ['feature_value' => 'true'],
            $features['manage_roommates']->id => ['feature_value' => 'true'],
            $features['manage_reports']->id => ['feature_value' => 'true'],
            $features['manage_managers']->id => ['feature_value' => 'true'],
            $features['avatar_frame']->id => ['feature_value' => 'gold'],
        ]);

        // Gói 2: Gói cơ bản (Miễn Phí sau khi hết dùng thử)
        $basicPlan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Gói Cơ Bản (Miễn Phí)'],
            [
                'price' => 0,
                'duration_days' => 3650,
                'badge' => 'CƠ BẢN',
                'sort_order' => 2,
                'description' => 'Dành cho chủ trọ nhỏ (1 cơ sở, tối đa 8 phòng).',
                'is_active' => true,
            ]
        );
        $basicPlan->features()->sync([
            $features['max_rooms']->id => ['feature_value' => '8'],
            $features['max_properties']->id => ['feature_value' => '1'],
            $features['vip_badge']->id => ['feature_value' => 'false'],
            $features['priority_listing']->id => ['feature_value' => '0'],
            $features['export_reports']->id => ['feature_value' => 'false'],
            $features['max_boarding_houses']->id => ['feature_value' => '1'],
            $features['max_listings']->id => ['feature_value' => '5'],
            $features['manage_invoices']->id => ['feature_value' => 'true'],
            $features['manage_contracts']->id => ['feature_value' => 'true'],
            $features['manage_roommates']->id => ['feature_value' => 'true'],
            $features['manage_reports']->id => ['feature_value' => 'true'],
            $features['manage_managers']->id => ['feature_value' => 'false'],
            $features['avatar_frame']->id => ['feature_value' => 'none'],
        ]);

        // Gói 3: Gói Chuyên Nghiệp
        $proPlan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Gói Chuyên Nghiệp'],
            [
                'price' => 1999000,
                'duration_days' => 30,
                'badge' => 'PHỔ BIẾN',
                'sort_order' => 3,
                'description' => 'Dành cho chủ trọ có 1-3 cơ sở (tối đa 30 phòng).',
                'is_active' => true,
            ]
        );
        $proPlan->features()->sync([
            $features['max_rooms']->id => ['feature_value' => '30'],
            $features['max_properties']->id => ['feature_value' => '3'],
            $features['vip_badge']->id => ['feature_value' => 'true'],
            $features['priority_listing']->id => ['feature_value' => '10'],
            $features['export_reports']->id => ['feature_value' => 'true'],
            $features['max_boarding_houses']->id => ['feature_value' => '3'],
            $features['max_listings']->id => ['feature_value' => '12'],
            $features['manage_invoices']->id => ['feature_value' => 'true'],
            $features['manage_contracts']->id => ['feature_value' => 'true'],
            $features['manage_roommates']->id => ['feature_value' => 'true'],
            $features['manage_reports']->id => ['feature_value' => 'true'],
            $features['manage_managers']->id => ['feature_value' => 'true'],
            $features['avatar_frame']->id => ['feature_value' => 'gold'],
        ]);

        // Gói 4: Gói Cao Cấp VIP
        $vipPlan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Gói Cao Cấp VIP'],
            [
                'price' => 499000,
                'duration_days' => 30,
                'badge' => 'VIP PREMIUM',
                'sort_order' => 4,
                'description' => 'Không giới hạn cơ sở & phòng, phân quyền nhân viên quản lý.',
                'is_active' => true,
            ]
        );
        $vipPlan->features()->sync([
            $features['max_rooms']->id => ['feature_value' => '-1'],
            $features['max_properties']->id => ['feature_value' => '-1'],
            $features['vip_badge']->id => ['feature_value' => 'true'],
            $features['priority_listing']->id => ['feature_value' => '-1'],
            $features['export_reports']->id => ['feature_value' => 'true'],
            $features['max_boarding_houses']->id => ['feature_value' => '-1'],
            $features['max_listings']->id => ['feature_value' => '-1'],
            $features['manage_invoices']->id => ['feature_value' => 'true'],
            $features['manage_contracts']->id => ['feature_value' => 'true'],
            $features['manage_roommates']->id => ['feature_value' => 'true'],
            $features['manage_reports']->id => ['feature_value' => 'true'],
            $features['manage_managers']->id => ['feature_value' => 'true'],
            $features['avatar_frame']->id => ['feature_value' => 'gold'],
        ]);
    }
}

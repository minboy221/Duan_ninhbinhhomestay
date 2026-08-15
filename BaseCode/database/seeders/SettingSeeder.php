<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSettings = [
            'hero_title' => 'Tìm Phòng Và Nhà Trọ Phù Hợp',
            'hero_subtitle' => 'Hệ thống tìm kiếm và quản lý phòng trọ thông minh số 1 tại Ninh Bình.',
            'contact_phone' => '0912 345 678',
            'contact_email' => 'contact@ninhbinhhomestay.vn',
            'contact_address' => 'Ninh Bình, Việt Nam',
            'contact_map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2611.291724627434!2d105.93314109429076!3d20.603915192384463!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135cf62d752dc67%3A0xd79f03899b4e83d8!2zVHLGsOG7nW5nIENhbyDEkeG6s25nIEZQVCBQb2x5dGVjaG5pYyBjxqEgc-G7nyBIw6AgTmFt!5e1!3m2!1svi!2s!4v1774600950495!5m2!1svi!2s',
            'banners' => json_encode([
                [
                    'id' => 1,
                    'title' => 'Banner chính trang chủ',
                    'img' => '/anh/banner.png',
                    'active' => true,
                    'order' => 1
                ],
                [
                    'id' => 2,
                    'title' => 'Banner khuyến mãi hè',
                    'img' => '/anh/banner.png',
                    'active' => false,
                    'order' => 2
                ]
            ], JSON_UNESCAPED_UNICODE)
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}

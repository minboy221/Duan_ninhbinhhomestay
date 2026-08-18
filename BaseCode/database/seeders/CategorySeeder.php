<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Area;
use App\Models\Amenity;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Loại phòng
        $categories = [
            ['name' => 'Phòng đơn',        'icon' => 'bi-door-closed'],
            ['name' => 'Phòng ghép',        'icon' => 'bi-people'],
            ['name' => 'Nhà nguyên căn',    'icon' => 'bi-house'],
            ['name' => 'Studio',            'icon' => 'bi-building'],
            ['name' => 'Căn hộ dịch vụ',   'icon' => 'bi-buildings'],
        ];
        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], $cat);
        }

        // Khu vực (Nạp đầy đủ 33 Phường/Xã thuộc Hà Nam từ hằng số HA_NAM_COMMUNES)
        $haNamCommunes = [
            "Xã Bình Lục",
            "Xã Bình Mỹ",
            "Xã Bình An",
            "Xã Bình Giang",
            "Xã Bình Sơn",
            "Xã Liêm Hà",
            "Xã Tân Thanh",
            "Xã Thanh Bình",
            "Xã Thanh Lâm",
            "Xã Thanh Liêm",
            "Xã Lý Nhân",
            "Xã Nam Xang",
            "Xã Bắc Lý",
            "Xã Vĩnh Trụ",
            "Xã Trần Thương",
            "Xã Nhân Hòa",
            "Xã Nam Lý",
            "Phường Duy Tiên",
            "Phường Duy Tân",
            "Phường Đồng Văn",
            "Phường Duy Hà",
            "Phường Tiên Sơn",
            "Phường Lê Hồ",
            "Phường Nguyễn Úy",
            "Phường Lý Thường Kiệt",
            "Phường Kim Thanh",
            "Phường Tam Chúc",
            "Phường Kim Bảng",
            "Phường Hà Nam",
            "Phường Phù Vân",
            "Phường Châu Sơn",
            "Phường Phủ Lý",
            "Phường Liêm Tuyền"
        ];

        foreach ($haNamCommunes as $communeName) {
            $data = [
                'name' => $communeName,
                'icon' => str_starts_with($communeName, 'Phường') ? 'bi-geo-alt-fill' : 'bi-geo-alt',
            ];
            if ($communeName === 'Phường Phủ Lý') {
                $data['map_embed'] = '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d59745!2d105.91!3d20.54!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135cf570d8a5725%3A0x8849b2512f455c11!2zVFAuIFBow7ogTMO9LCBIw6AgTmFt!5e0!3m2!1svi!2svn!4v1" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';
            }
            Area::firstOrCreate(['name' => $communeName], $data);
        }

        // Tiện ích phòng trọ (Danh sách tiện ích dịch vụ chuẩn)
        $amenities = [
            ['name' => 'Tiền điện',          'icon' => 'bi-lightning-charge'],
            ['name' => 'Tiền nước',          'icon' => 'bi-droplet'],
            ['name' => 'Internet/Wi-Fi',     'icon' => 'bi-wifi'],
            ['name' => 'Phí rác',            'icon' => 'bi-trash'],
            ['name' => 'Phí gửi xe',         'icon' => 'bi-bicycle'],
            ['name' => 'Phí dịch vụ/chung',  'icon' => 'bi-tools'],
            ['name' => 'Phí máy giặt',       'icon' => 'bi-layers'],
            ['name' => 'Phí điều hòa',       'icon' => 'bi-thermometer-snow'],
            ['name' => 'Phí phát sinh',      'icon' => 'bi-plus-circle'],
        ];
        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(['name' => $amenity['name']], $amenity);
        }

        $this->command->info('✅ Dữ liệu danh mục đã được tạo thành công!');
    }
}

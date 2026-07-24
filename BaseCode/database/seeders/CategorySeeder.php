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
            Category::create($cat);
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

        // Tiện ích phòng trọ
        $amenities = [
            ['name' => 'WiFi',            'icon' => 'bi-wifi'],
            ['name' => 'Điều hoà',        'icon' => 'bi-thermometer-snow'],
            ['name' => 'Nóng lạnh',       'icon' => 'bi-droplet-half'],
            ['name' => 'Bãi xe',          'icon' => 'bi-bicycle'],
            ['name' => 'Bảo vệ 24/7',    'icon' => 'bi-shield-check'],
            ['name' => 'Giặt sấy',        'icon' => 'bi-basket2'],
            ['name' => 'Tủ lạnh',         'icon' => 'bi-box-seam'],
            ['name' => 'Máy giặt',        'icon' => 'bi-layers'],
            ['name' => 'Bếp riêng',       'icon' => 'bi-cup-hot'],
            ['name' => 'Ban công',        'icon' => 'bi-sun'],
        ];
        foreach ($amenities as $amenity) {
            Amenity::create($amenity);
        }

        $this->command->info('✅ Dữ liệu danh mục đã được tạo thành công!');
    }
}

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

        // Khu vực (Huyện/Thành phố thuộc Ninh Bình) - có kèm mã nhúng Google Maps
        $areas = [
            ['name' => 'TP. Ninh Bình',   'icon' => 'bi-geo-alt-fill', 'map_embed' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d59745.20735948975!2d105.94!3d20.25!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3136814a12811cf3%3A0xd1e9fb9944fa5e68!2zVGjDoG5oIHBo4buRIE5pbmggQsOsbmg!5e0!3m2!1svi!2svn!4v1" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>'],
            ['name' => 'TP. Tam Điệp',    'icon' => 'bi-geo-alt-fill', 'map_embed' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d59808.74!2d105.88!3d20.16!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31368be8cebc5d65%3A0xf0eaebb211bce29!2zVGjDoG5oIHBo4buRIFRhbSBEaeG7h3A!5e0!3m2!1svi!2svn!4v1" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>'],
            ['name' => 'Hoa Lư',          'icon' => 'bi-geo-alt', 'map_embed' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d59770.21!2d105.89!3d20.28!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31368a8f64bc9aff%3A0xdce4bab4e9aa0b38!2zSG9hIEzGsCwgTmluaCBCw6xuaCwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2svn!4v1" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>'],
            ['name' => 'Gia Viễn',        'icon' => 'bi-geo-alt'],
            ['name' => 'Nho Quan',        'icon' => 'bi-geo-alt'],
            ['name' => 'Yên Khánh',       'icon' => 'bi-geo-alt'],
            ['name' => 'Kim Sơn',         'icon' => 'bi-geo-alt'],
            ['name' => 'Yên Mô',          'icon' => 'bi-geo-alt'],
        ];
        foreach ($areas as $area) {
            Area::create($area);
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

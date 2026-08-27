<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\BoardingHouseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class VerificationService
{
    protected $userRepository;
    protected $boardingHouseRepository;

    public function __construct(UserRepository $userRepository, BoardingHouseRepository $boardingHouseRepository)
    {
        $this->userRepository = $userRepository;
        $this->boardingHouseRepository = $boardingHouseRepository;
    }
    public function processVerification($userId, $data)
    {
        //dùng db transaction để khi lỗi ở các bước sẽ không lưu vào db để tránh dữ liệu rác
        DB::beginTransaction();
        try {
            //PHẦN XỬ LÝ BƯỚC 1 VÀ 3
            $timestamp = time();
            $this->userRepository->updateUser($userId, [
                'phone' => $data['phone']
            ]);
            // 1. Lưu file ảnh CCCD và khuôn mặt vào thư mục private
            Storage::disk('private')->makeDirectory('kyc/id_cards');
            Storage::disk('private')->makeDirectory('kyc/faces');
            Storage::disk('private')->makeDirectory('properties/contracts');
            Storage::disk('public')->makeDirectory('properties/rooms');

            // Ảnh mặt trước
            $extFront = $data['id_card_front']->getClientOriginalExtension() ?: 'jpg';
            $frontName = "user_{$userId}_cccd_truoc_{$timestamp}.{$extFront}";
            $frontPath = $data['id_card_front']->storeAs('kyc/id_cards', $frontName, 'private');
            if (!$frontPath) {
                Storage::disk('private')->put('kyc/id_cards/' . $frontName, file_get_contents($data['id_card_front']->getRealPath()));
                $frontPath = 'kyc/id_cards/' . $frontName;
            }

            // Ảnh mặt sau
            $extBack = $data['id_card_back']->getClientOriginalExtension() ?: 'jpg';
            $backName = "user_{$userId}_cccd_sau_{$timestamp}.{$extBack}";
            $backPath = $data['id_card_back']->storeAs('kyc/id_cards', $backName, 'private');
            if (!$backPath) {
                Storage::disk('private')->put('kyc/id_cards/' . $backName, file_get_contents($data['id_card_back']->getRealPath()));
                $backPath = 'kyc/id_cards/' . $backName;
            }

            // Ảnh khuôn mặt
            $extFace = $data['face_auth_image']->getClientOriginalExtension() ?: 'jpg';
            $faceName = "user_{$userId}_khuon_mat_{$timestamp}.{$extFace}";
            $facePath = $data['face_auth_image']->storeAs('kyc/faces', $faceName, 'private');
            if (!$facePath) {
                Storage::disk('private')->put('kyc/faces/' . $faceName, file_get_contents($data['face_auth_image']->getRealPath()));
                $facePath = 'kyc/faces/' . $faceName;
            }

            // Trạng thái từ AI gửi lên
            $kycStatus = $data['is_face_matched'] ? 'approved' : 'rejected';

            // 2. Chuẩn bị mảng dữ liệu theo schema
            $verificationData = [
                'id_card_number' => $data['id_card_number'] ?? null,
                'id_card_front' => $frontPath,
                'id_card_back' => $backPath,
                'face_auth_image' => $facePath,
                'kyc_status' => $kycStatus,
            ];
            // 3. Gọi repository để cập nhật db mới
            $this->userRepository->updateOrCreateVerification($userId, $verificationData);

            // PHẦN XỬ LÝ BƯỚC 2: Lưu thông tin trọ
            $contractPaths = [];
            $roomPaths = [];
            $finalLat = !empty($data['latitude']) && is_numeric($data['latitude']) ? (float) $data['latitude'] : null;
            $finalLng = !empty($data['longitude']) && is_numeric($data['longitude']) ? (float) $data['longitude'] : null;

            // Vòng lặp lưu từng ảnh hợp đồng
            if (isset($data['contract_images']) && is_array($data['contract_images'])) {
                foreach ($data['contract_images'] as $index => $image) {
                    if (!$image) continue;
                    $ext = $image->getClientOriginalExtension() ?: 'jpg';

                    // Thử quét GPS nếu chưa có tọa độ
                    if (!$finalLat && in_array(strtolower($ext), ['jpg', 'jpeg', 'heic'])) {
                        $exif = @exif_read_data($image->getRealPath());
                        if ($exif !== false) {
                            $gps = $this->getGpsFromExif($exif);
                            if ($gps) {
                                $finalLat = $gps['lat'];
                                $finalLng = $gps['lng'];
                            }
                        }
                    }

                    $name = "user_{$userId}_hop_dong_{$index}_{$timestamp}.{$ext}";
                    $stored = $image->storeAs('properties/contracts', $name, 'private');
                    if (!$stored) {
                        Storage::disk('private')->put('properties/contracts/' . $name, file_get_contents($image->getRealPath()));
                        $stored = 'properties/contracts/' . $name;
                    }
                    $contractPaths[] = $stored;
                }
            }

            // Lưu mảng ảnh không gian trọ
            if (isset($data['room_images']) && is_array($data['room_images'])) {
                foreach ($data['room_images'] as $index => $file) {
                    if (!$file) continue;
                    $ext = $file->getClientOriginalExtension() ?: 'jpg';

                    // Chỉ quét GPS nếu chưa có
                    if (!$finalLat && in_array(strtolower($ext), ['jpg', 'jpeg', 'heic'])) {
                        $exif = @exif_read_data($file->getRealPath());
                        if ($exif !== false) {
                            $gps = $this->getGpsFromExif($exif);
                            if ($gps) {
                                $finalLat = $gps['lat'];
                                $finalLng = $gps['lng'];
                            }
                        }
                    }
                    $name = "user_{$userId}_phong_tro_{$index}_{$timestamp}.{$ext}";
                    $stored = $file->storeAs('properties/rooms', $name, 'public');
                    if (!$stored) {
                        Storage::disk('public')->put('properties/rooms/' . $name, file_get_contents($file->getRealPath()));
                        $stored = 'properties/rooms/' . $name;
                    }
                    $roomPaths[] = $stored;
                }
            }

            $boardingHouseData = [
                'user_id' => $userId,
                'name' => $data['property_name'],
                'district' => $data['ward'],
                'address_detail' => $data['address_detail'],
                'contract_images' => array_values(array_filter($contractPaths)),
                'room_images' => array_values(array_filter($roomPaths)),
                'latitude' => $finalLat,
                'longitude' => $finalLng,
                'status' => 'pending',
            ];

            $this->boardingHouseRepository->createBoardingHouse($boardingHouseData);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    // Phần lấy toạ độ địa chỉ map khi xác minh
    private function gpsToDecimal($coordinate, $hemisphere = 'N')
    {
        if (is_numeric($coordinate)) {
            $val = (float) $coordinate;
            $ref = strtoupper(trim((string) $hemisphere));
            return ($ref === 'S' || $ref === 'W') ? -abs($val) : abs($val);
        }

        if (!is_array($coordinate)) {
            return 0;
        }

        $degrees = count($coordinate) > 0 ? $this->evalFraction($coordinate[0]) : 0;
        $minutes = count($coordinate) > 1 ? $this->evalFraction($coordinate[1]) : 0;
        $seconds = count($coordinate) > 2 ? $this->evalFraction($coordinate[2]) : 0;

        $ref = strtoupper(trim((string) $hemisphere));
        $flip = ($ref === 'W' || $ref === 'S') ? -1 : 1;
        return $flip * ($degrees + ($minutes / 60) + ($seconds / 3600));
    }

    private function evalFraction($fraction)
    {
        if (is_array($fraction)) {
            if (count($fraction) >= 2 && is_numeric($fraction[0]) && is_numeric($fraction[1])) {
                $num = (float) $fraction[0];
                $den = (float) $fraction[1];
                return $den != 0 ? $num / $den : 0;
            }

            if (count($fraction) > 0) {
                return $this->evalFraction($fraction[0]);
            }

            return 0;
        }

        if (is_string($fraction)) {
            $trimmed = trim($fraction);
            if ($trimmed === '') {
                return 0;
            }

            if (str_contains($trimmed, '/')) {
                $parts = explode('/', $trimmed, 2);
                $num = (float) trim($parts[0]);
                $den = (float) trim($parts[1]);
                return $den != 0 ? $num / $den : 0;
            }

            return (float) $trimmed;
        }

        if (is_numeric($fraction)) {
            return (float) $fraction;
        }

        return 0;
    }

    // phần lấy toạ độ
    private function getGpsFromExif($exif)
    {
        if (!$exif || !is_array($exif)) {
            return null;
        }

        // Lấy vĩ độ và kinh độ (hỗ trợ cả dạng flat array, nested GPS và EXIF section)
        $gpsLat = $exif['GPSLatitude'] ?? $exif['GPS']['GPSLatitude'] ?? $exif['EXIF']['GPSLatitude'] ?? null;
        $gpsLng = $exif['GPSLongitude'] ?? $exif['GPS']['GPSLongitude'] ?? $exif['EXIF']['GPSLongitude'] ?? null;
        $latRef = $exif['GPSLatitudeRef'] ?? $exif['GPS']['GPSLatitudeRef'] ?? $exif['EXIF']['GPSLatitudeRef'] ?? 'N';
        $lngRef = $exif['GPSLongitudeRef'] ?? $exif['GPS']['GPSLongitudeRef'] ?? $exif['EXIF']['GPSLongitudeRef'] ?? 'E';

        if ($gpsLat && $gpsLng) {
            $lat = $this->gpsToDecimal($gpsLat, $latRef);
            $lng = $this->gpsToDecimal($gpsLng, $lngRef);

            if ($lat != 0 && $lng != 0) {
                return ['lat' => round($lat, 8), 'lng' => round($lng, 8)];
            }
        }
        return null;
    }
}
?>
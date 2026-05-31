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
            //1. phần này sẽ lưu file ảnh cccd vào thư mục private (storage/app\private/kyc) và mỗi file ảnh sẽ có tên user riêng
            //Ảnh mặt trước
            $extFront = $data['id_card_front']->getClientOriginalExtension(); //lấy đuôi file ảnh
            $frontName = "user_{$userId}_cccd_truoc_{$timestamp}.{$extFront}"; //tạo tên mới
            $frontPath = $data['id_card_front']->storeAs('private/kyc/id_cards', $frontName, 'local');

            //Ảnh mặt sau
            $extBack = $data['id_card_back']->getClientOriginalExtension();
            $backName = "user_{$userId}_cccd_sau_{$timestamp}.{$extFront}";
            $backPath = $data['id_card_back']->storeAs('private/kyc/id_cards', $backName, 'local');

            //Ảnh khuôn mặt
            $extFace = $data['face_auth_image']->getClientOriginalExtension();
            $faceName = "user_{$userId}_khuon_mat_{$timestamp}.{$extFace}";
            $facePath = $data['face_auth_image']->storeAs('private/kyc/faces', $faceName, 'local');

            //trạng thái từ AI gửi lên
            $kycStatus = $data['is_face_matched'] ? 'approved' : 'rejected';

            //2.chuẩn bị mảng dữ liệu theo schema mới
            $verificationData = [
                'id_card_number' => $data['id_card_number'] ?? null,
                'id_card_front' => $frontPath,
                'id_card_back' => $backPath,
                'face_auth_image' => $facePath,
                'kyc_status' => $kycStatus,
            ];
            //3. gọi repository để cập nhật db mới
            $this->userRepository->updateOrCreateVerification($userId, $verificationData);

            //PHẦN XỬ LÝ BƯỚC 2 lưu thông tin trọ
            $contractPaths = [];
            $roomPaths = [];

            //vòng lặp lưu từng ảnh hợp đồng
            if (isset($data['contract_images'])) {
                foreach ($data['contract_images'] as $index => $image) {
                    $ext = $image->getClientOriginalExtension();
                    $name = "user_{$userId}_hop_dong_{$index}_{$timestamp}.{$ext}";
                    $contractPaths[] = $image->storeAs('private/properties/contracts', $name, 'local');
                }
            }
            // Lưu mảng ảnh không gian trọ
            if (isset($data['room_images'])) {
                foreach ($data['room_images'] as $index => $image) {
                    $ext = $image->getClientOriginalExtension();
                    $name = "user_{$userId}_phong_tro_{$index}_{$timestamp}.{$ext}";
                    $roomPaths[] = $image->storeAs('private/properties/rooms', $name, 'local');
                }
            }
            $boardingHouseData = [
                'user_id' => $userId,
                'name' => $data['property_name'],
                'district' => $data['district'],
                'address_detail' => $data['address_detail'],
                'contract_images' => $contractPaths,
                'room_images' => $roomPaths,
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
}
?>
<?php
namespace App\Services;

use App\Repositories\Interfaces\BoardingHouseRepositoryInterface;
use App\Models\User;
use App\Notifications\AdminNotification;

class BoardingHouseService
{
    protected $boardingHouseRepository;

    public function __construct(BoardingHouseRepositoryInterface $boardingHouseRepository)
    {
        $this->boardingHouseRepository = $boardingHouseRepository;
    }

    public function createBoardingHouse(array $data, $roomImages, $contractImages, $userId, $userName)
    {
        $contractImagesPath = [];
        if ($contractImages) {
            foreach ($contractImages as $file) {
                $path = $file->store('boarding_houses/contracts', 'public');
                $contractImagesPath[] = $path;
            }
        }

        $roomImagesPath = [];
        if ($roomImages) {
            foreach ($roomImages as $file) {
                $path = $file->store('boarding_houses/rooms', 'public');
                $roomImagesPath[] = $path;
            }
        }

        $data['user_id'] = $userId;
        $data['contract_images'] = json_encode($contractImagesPath);
        $data['room_images'] = json_encode($roomImagesPath);
        $data['status'] = 'pending';

        $house = $this->boardingHouseRepository->createBoardingHouse($data);

        // Gửi thông báo cho Admin
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new AdminNotification(
                'Có cơ sở mới cần duyệt',
                'Chủ trọ ' . $userName . ' vừa tạo thêm cơ sở mới: ' . $house->name,
                'new_boarding_house',
                '/admin/boarding-houses/' . $house->id
            ));
        }

        return $house;
    }

    public function approveBoardingHouse(int $id)
    {
        $this->boardingHouseRepository->updateStatus($id, 'approved');
        $house = $this->boardingHouseRepository->findById($id);

        $user = User::find($house->user_id);
        if ($user) {
            $user->notify(new AdminNotification(
                'Cơ sở mới đã được duyệt',
                'Cơ sở "' . $house->name . '" của bạn đã được quản trị viên phê duyệt. Bạn đã có thể bắt đầu đăng phòng trên cơ sở này.',
                'boarding_house_approved',
                '/landlord/boarding-houses'
            ));
        }
        return $house;
    }

    public function rejectBoardingHouse(int $id, string $reason)
    {
        $this->boardingHouseRepository->updateStatus($id, 'rejected');
        $house = $this->boardingHouseRepository->findById($id);

        $user = User::find($house->user_id);
        if ($user) {
            $user->notify(new AdminNotification(
                'Cơ sở mới bị từ chối',
                'Cơ sở "' . $house->name . '" của bạn đã bị từ chối. Lý do: ' . $reason,
                'boarding_house_rejected',
                '/landlord/boarding-houses'
            ));
        }
        return $house;
    }

    public function getPendingBoardingHouses()
    {
        return $this->boardingHouseRepository->getPendingBoardingHouses();
    }

    public function getLandlordBoardingHouses(int $userId)
    {
        return $this->boardingHouseRepository->getLandlordBoardingHouses($userId);
    }

    public function getLandlordBoardingHousesHistory(int $userId)
    {
        return $this->boardingHouseRepository->getLandlordBoardingHousesHistory($userId);
    }

    public function findById(int $id)
    {
        return $this->boardingHouseRepository->findById($id);
    }
}

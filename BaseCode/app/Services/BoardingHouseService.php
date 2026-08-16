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

    //logic đồng bộ địa chỉ xuống tầng & phòng, và kiểm tra ràng buộc hợp đồng
    public function updateBoardingHouse(int $id, array $data, $roomImages, $contractImages, int $userId)
    {
        $house = \App\Models\BoardingHouse::where('id',$id)->where('user_id',$userId)->firstOrFail();
        //xử lý ảnh hợp đồng mẫu nếu có tải lên mới
        if($contractImages){
            $contractImagesPath  = [];

            foreach($contractImages as $file){
                $contractImagesPath[] = $file->store('boarding_houses/contracts','public');
            }
            $data['contract_images'] = json_encode($contractImagesPath);
        }
        //xử lý ảnh cơ sở nếu có ảnh tải lên mới
        if($roomImages){
            $roomImagesPath = [];
            foreach($roomImages as $file){
                $roomImagesPath[] = $file->store('boarding_houses/rooms','public');
            }
            $data['room_images'] = json_encode($roomImagesPath);
        }
        //cập nhật thông tin cơ sở trọ
        $house->update($data);

        //đồng bộ hoá địa chỉ và toạ độ mới xuống phòng và tầng của cơ sở
        $fullAddress = $house->address_detail . ($house->district ? ',' .$house->distinct : '');
        //cập nhật phòng
        \App\Models\Room::where('boarding_house_id',$house->id)->update([
            'address' => $fullAddress,
        ]);
        //cập nhật tầng
        $floorIds = \App\Models\Room::where('boarding_house_id',$house->id)
        ->pluck('floor_id')
        ->unique()
        ->filter()
        ->toArray();
        if(!empty($floorIds)){
            \App\Models\Floor::whereIn('id',$floorIds)->update([
                'address' => $fullAddress,
                'latitude' => $house->latitude,
                'longitude' => $house->longitude,
            ]);
        }
        return $house;
    }
    public function deleteBoardingHouse(int $id, int $userId){
        $house = \App\Models\BoardingHouse::where('id',$id)->where('user_id',$userId)->firstOrFail();
        //chặn nếu cơ sở trọ này đang có bài đăng tin rao phòng public trên hệ thống
        $hasActivePosts = \App\Models\RoomPost::whereHas('room',function ($q) use ($id){
            $q->where('boarding_house_id',$id);
        })->whereIn('status',['published','approved','pending'])->exists();
        if($hasActivePosts){
            throw new \Exception('Không thể xoá cơ sở này vì đang có Bài đăng tin rao phòng công khai trên hệ thống. Vui lòng gỡ tin đăng trước!');
        }
        //Chặn nếu cơ sở trọ này đang có hợp đồng thuê trọ còn hiệu lực
        $hasActiveContracts = \App\Models\Contract::whereHas('room', function($q) use ($id){
            $q->where('boarding_house_id',$id);
        })->whereIn('status',['active', 'signed', 'awaiting_upload', 'pending_renewal'])->exists();
        if($hasActiveContracts){
            throw new \Exception('Không thể xoá cơ sở này vì vẫn còn hợp đồng đang có hiệu lực!');
        }
        \App\Models\Room::where('boarding_house_id',$house->id)->delete();
        //xoá cơ sở
        $house->delete();
        return true;
    }
}

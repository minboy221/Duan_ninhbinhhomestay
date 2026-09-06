<?php
namespace App\Services;

use App\Models\User;
use App\Models\UserVerification;
use App\Models\BoardingHouse;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\PhpProcess;


class AdminVerificationService
{
    //lấy danh sách hồ sơ của người dùng đăng ký chủ trọ
    public function getVerificationsList()
    {
        return User::query()
            ->where(function ($query) {
                $query->whereHas('verification')
                    ->orWhereHas('boardingHouse');
            })
            ->with(['verification', 'boardingHouse'])
            ->orderBy('id', 'desc') //đưa các hồ sơ mới nhất lên đầu
            ->paginate(10);
    }
    //lấy thông tin chi tiết hồ sơ
    public function getVerificationDetail($userId)
    {
        $user = \App\Models\User::with(['verification', 'boardingHouse'])->findOrFail($userId);
        return [
            'user' => $user,
            'verification' => $user->verification,
            'boardingHouse' => $user->boardingHouse
        ];
    }

    //Phần xử lý logic Duyệt/Từ chối
    public function processStatusUpdate($userId, $action, $reason = null)
    {
        DB::beginTransaction();
        try {
            if ($action === 'approve') {
                // 1. Nâng cấp tài khoản thành chủ trọ (Gán thuộc tính trực tiếp)
                $userToApprove = User::find($userId);
                if ($userToApprove) {
                    $userToApprove->role = 'landlord';
                    $userToApprove->save();
                }
                // Kích hoạt nhà trọ
                BoardingHouse::where('user_id', $userId)->update(['status' => 'approved']);

                // Đánh dấu hồ sơ Kyc đã được admin duyệt
                UserVerification::where('user_id', $userId)->update(['kyc_status' => 'approved']);

                // Gửi thông báo
                $userToApprove->notify(new \App\Notifications\LandlordApproved());

                $message = 'Đã duyệt hồ sơ và cấp quyền Chủ trọ thành công';
            } else {
                //từ chối nhà trọ
                BoardingHouse::where('user_id', $userId)->update(['status' => 'rejected']);
                //(tuỳ chọn) lưu lý do từ chối vào bảng verifications để hiện thị cho user
                UserVerification::where('user_id', $userId)->update(['kyc_status' => 'rejected', 'kyc_notes' => $reason]);

                // Gửi thông báo từ chối
                $userToReject = User::find($userId);
                if ($userToReject) {
                    $userToReject->notify(new \App\Notifications\LandlordRejected($reason));
                }

                $message = 'Đã từ chối hồ sơ xác minh';
            }
            DB::commit();
            return ['success' => true, 'message' => $message];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    //hàm xoá hồ sơ của admin
    protected function deleteCloudflareFile(?string $pathOrUrl): void
    {
        if (empty($pathOrUrl))
            return;
        //lấy url cloudflare r2
        $r2PublicUrl = rtrim(config('filesystems.disks.r2_public.url', ''), '/');
        $r2PrivateUrl = rtrim(config('filesystems.disks.r2_private.url', ''), '/');
        $relativePath = $pathOrUrl;
        if (!empty($r2PublicUrl) && str_starts_with($pathOrUrl, $r2PublicUrl)) {
            $relativePath = ltrim(substr($pathOrUrl, strlen($r2PublicUrl)), '/');
        } elseif (!empty($r2PrivateUrl) && str_starts_with($pathOrUrl, $r2PrivateUrl)) {
            $relativePath = ltrim(substr($pathOrUrl, strlen($r2PrivateUrl)), '/');
        } else {
            // Loại bỏ tiền tố /storage/ nếu có
            $relativePath = ltrim($pathOrUrl, '/storage/');
        }
        $disks = ['r2_private', 'r2_public', 'public'];
        foreach ($disks as $disk) {
            try {
                if (config("filesystems.disks.{$disk}") && Storage::disk($disk)->exists($relativePath)) {
                    Storage::disk($disk)->delete($relativePath);
                }
            } catch (\Throwable $e) {
            }
        }
    }
    //xoá hồ sơ không được duyệt
    public function deleteRejectedVerification(int $userId): bool
    {
        DB::beginTransaction();
        try {
            //tìm hồ sơ KYC
            $verification = UserVerification::where('user_id', $userId)->first();
            if (!$verification) {
                throw new Exception("Không tìm thấy hồ sơ xác minh của người dùng này!");
            }
            //dàng buộc không xoá hồ sơ đã duyệt
            if ($verification->kyc_status === 'approved') {
                throw new Exception("Hồ sơ này đã được chấp thuận duyệt làm chủ trọ. Không thể xoá!");
            }
            //xoá các file ảnh
            $this->deleteCloudflareFile($verification->id_card_front);
            $this->deleteCloudflareFile($verification->id_card_back);
            $this->deleteCloudflareFile($verification->face_auth_image);
            $boardingHouse = BoardingHouse::where('user_id', $userId)->first();
            if ($boardingHouse && in_array($boardingHouse->status, ['rejected', 'pending'])) {
                //xoá ảnh hợp đồng/phòng
                if (is_array($boardingHouse->contract_images)) {
                    foreach ($boardingHouse->contract_images as $img) {
                        $this->deleteCloudflareFile($img);
                    }
                }
                if (is_array($boardingHouse->room_images)) {
                    foreach ($boardingHouse->room_images as $img) {
                        $this->deleteCloudflareFile($img);
                    }
                }
                $boardingHouse->delete();
            }
            //xoá bản ghi
            $result = $verification->delete();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
?>
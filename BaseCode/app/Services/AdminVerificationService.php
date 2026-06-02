<?php
namespace App\Services;

use App\Models\User;
use App\Models\UserVerification;
use App\Models\BoardingHouse;
use Illuminate\Support\Facades\DB;
use Exception;


class AdminVerificationService
{
    //lấy danh sách hồ sơ của người dùng đăng ký chủ trọ
    public function getVerificationsList()
    {
        return User::where('role', 'user')
            ->whereHas('verification', function ($query) {
                $query->where('kyc_status', '!=', 'approved');
            })
            ->with('verification')
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
                //1.Nâng cấp tài khoản thành chủ trọ
                $userToApprove = User::find($userId);
                $userToApprove->update(['role' => 'landlord']);
                //Kích hoạt nhà trọ
                BoardingHouse::where('user_id', $userId)->update(['status' => 'approved']);
                //Phần đánh dấu hồ sơ Kyc đã được admin  duyệt
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
}
?>
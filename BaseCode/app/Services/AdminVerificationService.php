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
        return User::whereHas('verification')->with('verification')->paginate(10);
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
                User::where('id', $userId)->update(['role' => 'landlord']);
                //Kích hoạt nhà trọ
                BoardingHouse::where('user_id', $userId)->update(['status' => 'active']);
                //Phần đánh dấu hồ sơ Kyc đã được admin  duyệt
                UserVerification::where('user_id', '$userId')->update(['admin_status' => 'approved']);
                $message = 'Đã duyệt hồ sơ và cấp quyền Chủ trọ thành công';
            } else {
                //từ chối nhà trọ
                BoardingHouse::where('user_id', $userId)->update(['status' => 'rejected']);
                //(tuỳ chọn) lưu lý do từ chối vào bảng verifications để hiện thị cho user
                UserVerification::where('user_id', $userId)->update(['admin_status' => 'rejected', 'reject_reason' => $reason]);
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
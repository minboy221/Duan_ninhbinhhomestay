<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VerificationService;
use App\Http\Requests\VerifyLandlordRequest;
use App\Models\User;
use App\Notifications\NewLandlordApplication;
use Illuminate\Support\Facades\Notification;
use App\Services\AdminVerificationService;

class VerificationController extends Controller
{
    protected $verificationService;

    //truyền service vào Controller
    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }
    public function index()
    {
        $users = $this->adminVerificationService->getVerificationsList();

        return \Inertia\Inertia::render('Admin/Verifications/Index', [
            'users' => $users
        ]);
    }

    //phần hiển thị giao diện form 
    public function create()
    {
        return inertia('Landlord/Verification/Index');
    }

    //hàm xử lý dữ liệu submit
    public function verify(VerifyLandlordRequest $request)
    {
        //lấy thônng tin từ user đang đăng nhập
        $user = auth()->user();
        //gọi service xử lý lưu file và db
        $verificationResult = $this->verificationService->processVerification(
            $user->id,
            $request->validated() //lấy dữ liệu đã qua validate
        );

        // Gửi thông báo cho toàn bộ Admin
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new NewLandlordApplication($user));

        //gửi request đến inertia
        if ($request->header('X-Inertia')) {
            return redirect('/')->with('success', 'đã tải lên thông tin xác minh thành công. đang xử lý');
        }
        //trả kết quả ra json
        return response()->json([
            'status' => 'success',
            'message' => 'đã tải lên thông tin xác minh thành công',
            'data' => $verificationResult
        ], 200);
    }
}

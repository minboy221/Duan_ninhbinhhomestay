<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VerificationService;
use App\Http\Requests\VerifyLandlordRequest;

class VerificationController extends Controller
{
    protected $verificationService;

    //truyền service vào Controller
    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
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
        //gửi request đến inertia
        if ($request->inertia()) {
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

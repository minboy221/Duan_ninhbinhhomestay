<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AdminVerificationService;
use Illuminate\Support\Facades\Storage;

class AdminVerificationController extends Controller
{
    protected $adminVerificationService;
    //lấy services vào controller qua constructor
    public function __construct(AdminVerificationService $adminVerificationService)
    {
        $this->adminVerificationService = $adminVerificationService;
    }
    public function index()
    {
        $users = $this->adminVerificationService->getVerificationsList();

        return inertia('Admin/Verifications/Index', [
            'users' => $users
        ]);
    }
    //phần hiển thị
    public function show($userId)
    {
        $data = $this->adminVerificationService->getVerificationDetail($userId);
        return inertia('Admin/Verifications/Show', [
            'user' => $data['user'],
            'verification' => $data['verification'],
            'boardingHouse' => $data['boardingHouse']
        ]);
    }
    public function updateStatus(Request $request, $userId)
    {
        //validate dữ liệu từ frontend gửi lên
        $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'nullable|string'
        ]);

        try {
            $result = $this->adminVerificationService->processStatusUpdate(
                $userId,
                $request->action,
                $request->reason
            );
            return redirect()->route('admin.verifications.index')->with('success', $result['message']);
        } catch (\Exception $e) {
            return back()->with('error', 'có lỗi hệ thống xảy ra:' . $e->getMessage());
        }
    }
    //hàm đọc ảnh từ file private
    public function showPrivateFile($type, $filename)
    {
        // 1. Phân loại thư mục
        $folder = '';
        if (in_array($type, ['id_cards', 'faces'])) {
            $folder = "kyc/" . $type;
        } elseif (in_array($type, ['contracts', 'rooms'])) {
            $folder = "properties/" . $type;
        } else {
            abort(404);
        }

        // 2. Ghép đường dẫn tương đối (để check exists)
        $path = "private/" . $folder . "/" . $filename;

        // 3. Ghép đường dẫn tuyệt đối (để trả về file)
        $fullPath = storage_path("app/" . $path);

        // 4. Kiểm tra xem file có tồn tại không
        if (!\Storage::disk('local')->exists($path)) {
            abort(404, 'Không tìm thấy file ảnh thực tế trên hệ thống.');
        }

        // 5. Trả file ra ngoài (Sử dụng biến $fullPath an toàn 100%)
        return response()->file($fullPath);
    }
}

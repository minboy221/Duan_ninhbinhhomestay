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
            //ghi log
            $action = $request->action === 'approve' ? 'approve_verification' : 'reject_verification';
            $actionText = $request->action === 'approve' ? 'Phê duyệt' : 'Từ chối';
            //tìm thông tin email của người dùng bị tác động
            $targetUser = \App\Models\User::find($userId);
            $email = $targetUser ? $targetUser->email : 'ID # {$userId}';
            $reasonText = $request->reason ? "(Lý do: " . $request->reason . ")" : "";
            \App\Services\AuditLogger::log(
                $action,
                "{$actionText} hồ sơ làm chủ trọ của tài khoản: {$email}{$reasonText}",
                false
            );
            return redirect()->route('admin.verifications.index')->with('success', $result['message']);
        } catch (\Exception $e) {
            return back()->with('error', 'có lỗi hệ thống xảy ra:' . $e->getMessage());
        }
    }
    //hàm đọc ảnh từ file private
    public function showPrivateFile($type, $filename)
    {
        //phân quyền: Kiểm tra user đăng nhập có quyền xem không
        $user = auth()->user();
        if ($user && $user->role !== 'admin') {
            if (!str_contains($filename, 'user_' . $user->id . '_')) {
                abort(403, 'Không có quyền truy cập file này.');
            }
        }

        // 1. Phân loại thư mục
        $folder = '';
        if (in_array($type, ['id_cards', 'faces'])) {
            $folder = "kyc/" . $type;
        } elseif (in_array($type, ['contracts'])) {
            $folder = "properties/" . $type;
        } elseif ($type === 'rooms') {
            $publicPath = "properties/rooms/" . $filename;
            if (\Storage::disk('r2_public')->exists($publicPath)) {
                return redirect(\Storage::disk('r2_public')->url($publicPath));
            }
            abort(404, 'Không tìm thấy ảnh phòng trọ trên R2 Public');
        } else {
            abort(404);
        }

        // 2. Ghép đường dẫn tương đối (để check exists)
        $path = $folder . "/" . $filename;

        // 3.  Kiểm tra xem file có tồn tại trên R2 Private không
        if (!\Storage::disk('r2_private')->exists($path)) {
            abort(404, 'Không tìm thấy file ảnh thực tế trên hệ thống Cloudflare R2.');
        }

        // 4. Tạo link tạm thời tự hết hạn sau 10 phút để hiển thị an toàn
        $temporaryUrl = \Storage::disk('r2_private')->temporaryUrl($path, now()->addMinutes(10));
        return redirect($temporaryUrl);
    }
}

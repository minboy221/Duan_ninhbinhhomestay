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
    // Hàm đọc ảnh từ file private/public một cách an toàn cho Admin và chủ sở hữu
    public function showPrivateFile($type, $filename)
    {
        // Phân quyền: Kiểm tra user đăng nhập có quyền xem không
        $user = auth()->user();
        if ($user && $user->role !== 'admin') {
            if (!str_contains($filename, 'user_' . $user->id . '_')) {
                abort(403, 'Không có quyền truy cập file này.');
            }
        }

        $cleanFilename = basename(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filename));
        $candidatePaths = [];

        if (in_array($type, ['id_cards', 'faces'])) {
            $candidatePaths[] = storage_path('app/private/kyc/' . $type . '/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/public/kyc/' . $type . '/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/private/' . $type . '/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/private/kyc/' . $cleanFilename);
        } elseif (in_array($type, ['contracts'])) {
            $candidatePaths[] = storage_path('app/private/properties/contracts/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/public/properties/contracts/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/public/boarding_houses/contracts/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/private/boarding_houses/contracts/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/private/contracts/' . $cleanFilename);
        } elseif ($type === 'rooms') {
            $candidatePaths[] = storage_path('app/public/properties/rooms/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/private/properties/rooms/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/public/boarding_houses/rooms/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/private/boarding_houses/rooms/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/public/rooms/' . $cleanFilename);
            $candidatePaths[] = storage_path('app/private/rooms/' . $cleanFilename);
        }

        foreach ($candidatePaths as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                return response()->file($candidate);
            }
        }

        // Quét tìm trong private hoặc public nếu file nằm ở thư mục khác
        $privateMatches = glob(storage_path('app/private/**/' . $cleanFilename));
        if (!empty($privateMatches) && is_file($privateMatches[0])) {
            return response()->file($privateMatches[0]);
        }

        $publicMatches = glob(storage_path('app/public/**/' . $cleanFilename));
        if (!empty($publicMatches) && is_file($publicMatches[0])) {
            return response()->file($publicMatches[0]);
        }

        abort(404, 'Không tìm thấy file ảnh thực tế trên hệ thống.');
    }
}

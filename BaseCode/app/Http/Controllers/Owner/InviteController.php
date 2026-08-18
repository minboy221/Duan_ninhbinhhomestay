<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\PropertyManager;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InviteController extends Controller
{
    //phần hiển thị danh sách tài khoản đang được phân quyền quản lý
    public function index()
    {
        //check gói dịch vụ có hỗ trợ tính năng manage_managers không
        if (!auth()->user()->hasFeature('manage_managers')) {
            return redirect()->route('landlord.dashboard')->with('error', 'Gói dịch vụ hiện tại của bạn không hỗ trợ tính năng Phân quyền nhân viên / Quản lý phụ. Vui lòng nâng cấp gói.');
        }
        $boardingHouse = BoardingHouse::where('user_id', auth()->id())->get();
        $houseIds = $boardingHouse->pluck('id');
        $managers = PropertyManager::with(['user', 'boardingHouse'])->whereIn('boarding_house_id', $houseIds)
            ->get();
        return Inertia::render('Landlord/Managers/Index', [
            'managers' => $managers,
            'boardingHouses' => $boardingHouse
        ]);
    }
    //chủ trọ sinh mã QR phân quyền
    public function generateQr(Request $request, BoardingHouse $boardingHouse)
    {
        //check gói dịch vụ có hỗ trợ tính năng manage_managers không
        if (!auth()->user()->hasFeature('manage_managers')) {
            return response()->json(['message' => 'Gói dịch vụ hiện tại của bạn không hỗ trợ tính năng Phân quyền Nhân viên / Quản lý phụ. Vui lòng nâng cấp gói!']);
        }
        //check chỉ chủ trọ chính mới được tạo mã QR
        if (auth()->id() !== $boardingHouse->user_id) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
        }
        $request->validate([
            'permissions' => 'required|array'
        ]);
        //không cho phép giao toàn bộ phần quản lý
        if (count($request->permissions) >= 5) {
            return response()->json([
                'message' => 'Bạn không được phép giao toàn quyền quản lý. tài khoản phụ phải bị giới hạn ít nhất 1 chức năng.'
            ], 422);
        }
        //mỗi cơ sở trọ chỉ được phân quyền quản lý tối đa cho 1 tài khoản
        $existingManager = PropertyManager::where('boarding_house_id', $boardingHouse->id)->exists();
        if ($existingManager) {
            return response()->json([
                'message' => 'Cơ sở này đã được phân quyền cho 1 tài khoản rồi. Vui lòng hủy quyền tài khoản cũ trước khi cấp quyền mới.'
            ], 422);
        }
        $permissionString = implode(',', $request->permissions);
        //tạo signed url tự huỷ sau 15 phút
        $inviteUrl = URL::temporarySignedRoute('manager.invite.accept', now()->addMinute(15), [
            'house' => $boardingHouse->id,
            'permissions' => $permissionString
        ]);
        return response()->json(['url' => $inviteUrl]);
    }
    //người quản lý quét mã QR để nhận quyền
    public function accept(Request $request)
    {
        $houseId = $request->query('house');
        $permission = explode(',', $request->query('permissions'));
        $user = auth()->user();
        $boardingHouse = BoardingHouse::findOrFail($houseId);
        if ($user->id === $boardingHouse->user_id) {
            return redirect()->route('landlord.dashboard')->with('error', 'bạn đang là chủ sở hữu chính của khu trọ này.');
        }
        //ràng buộc khi quét mã check xem cơ sở này đã có người quản lý khác chưa
        $existingManager = PropertyManager::where('boarding_house_id', $houseId)->first();
        if ($existingManager && $existingManager->user_id !== $user->id) {
            return redirect()->route('landlord.dashboard')->with('error', 'Cơ sở này đã có tài khoản quản lý phụ rồi.');
        }
        //nâng cấp vai trò người dùng lên landlord
        if (in_array($user->role, ['tenant', 'user', 'client'])) {
            $user->role = 'landlord';
            $user->save();
        }
        PropertyManager::updateOrCreate(
            [
                'boarding_house_id' => $houseId,
                'user_id' => $user->id,
            ],
            [
                'permissions' => $permission
            ]
        );
        return redirect()->route('landlord.dashboard')->with('success', 'Bạn đã nhận quyền đồng quản lý thành công!');
    }

    //phần cập nhật quyền hạn của tài khoản phụ
    public function update(Request $request, PropertyManager $manager)
    {
        //xác thực cơ sở trọ thuộc sở hữu của chủ trọ hiện tại
        if ($manager->boardingHouse->user_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền thực hiện thực hiện hành động này!.');
        }
        $request->validate([
            'permissions' => 'required|array|min:1'
        ]);
        if (count($request->permissions) >= 5) {
            return redirect()->back()->with('error', 'Tài khoản phị phải giới hạn ít nhất 1 chức năng.');
        }
        $manager->update([
            'permissions' => $request->permissions
        ]);
        return redirect()->back()->with('success', 'Cập nhật quyền quản lý thành công!');
    }
    //huỷ quyền quản lý của tài khoản phụ
    public function destroy(PropertyManager $manager)
    {
        //xác thực xem cơ sở trọ này có thuộc chủ trọ hiện tại không
        if ($manager->boardingHouse->user_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }
        $manager->delete();
        return redirect()->back()->with('success', 'Đã huỷ quyền đồng quản lý thành công.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\BoardingHouse;
use App\Notifications\AdminNotification;
use App\Models\User;

class AdminBoardingHouseController extends Controller
{
    public function index()
    {
        $pendingHouses = BoardingHouse::with('user:id,name,email,phone')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/BoardingHouses/Index', [
            'pendingHouses' => $pendingHouses
        ]);
    }

    public function show($id)
    {
        $house = BoardingHouse::with('user')->findOrFail($id);
        return Inertia::render('Admin/BoardingHouses/Show', [
            'house' => $house
        ]);
    }

    public function approve($id)
    {
        $house = BoardingHouse::findOrFail($id);
        $house->status = 'approved';
        $house->save();

        // Gửi thông báo cho chủ trọ
        $user = User::find($house->user_id);
        if ($user) {
            $user->notify(new AdminNotification(
                'Cơ sở mới đã được duyệt',
                'Cơ sở "' . $house->name . '" của bạn đã được quản trị viên phê duyệt. Bạn đã có thể bắt đầu đăng phòng trên cơ sở này.',
                'boarding_house_approved',
                '/landlord/boarding-houses'
            ));
        }

        return redirect()->back()->with('success', 'Đã duyệt cơ sở thành công!');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        $house = BoardingHouse::findOrFail($id);
        $house->status = 'rejected';
        $house->save();

        // Gửi thông báo từ chối
        $user = User::find($house->user_id);
        if ($user) {
            $user->notify(new AdminNotification(
                'Cơ sở mới bị từ chối',
                'Cơ sở "' . $house->name . '" của bạn đã bị từ chối. Lý do: ' . $request->reason,
                'boarding_house_rejected',
                '/landlord/boarding-houses'
            ));
        }

        return redirect()->back()->with('success', 'Đã từ chối cơ sở thành công!');
    }
}

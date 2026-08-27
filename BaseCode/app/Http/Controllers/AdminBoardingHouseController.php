<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\BoardingHouseService;

class AdminBoardingHouseController extends Controller
{
    protected $boardingHouseService;

    public function __construct(BoardingHouseService $boardingHouseService)
    {
        $this->boardingHouseService = $boardingHouseService;
    }

    public function index()
    {
        $pendingHouses = $this->boardingHouseService->getPendingBoardingHouses();

        return Inertia::render('Admin/BoardingHouses/Index', [
            'pendingHouses' => $pendingHouses
        ]);
    }

    public function show($id)
    {
        $house = $this->boardingHouseService->findById($id);
        return Inertia::render('Admin/BoardingHouses/Show', [
            'house' => $house
        ]);
    }

    public function approve($id)
    {
        $this->boardingHouseService->approveBoardingHouse($id);
        //ghi log
        \App\Services\AuditLogger::log(
            'approve_boarding_house',
            "Phê duyệt cơ sở trọ mới: " . ($house->name ?? "ID # {$id}"),
            false
        );
        return redirect()->back()->with('success', 'Đã duyệt cơ sở thành công!');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        $this->boardingHouseService->rejectBoardingHouse($id, $request->reason);
        \App\Services\AuditLogger::log(
            'reject_boarding_house',
            "Từ chối cơ sở trọ mới:" . ($house->name ?? "ID #{$id}") . ". Lý do: " . $request->reason,
            false
        );
        return redirect()->back()->with('success', 'Đã từ chối cơ sở thành công!');
    }
}

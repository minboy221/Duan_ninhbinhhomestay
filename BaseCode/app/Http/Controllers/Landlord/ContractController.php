<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contract;
use App\Models\Room;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\ContractSignedEvent;
use Inertia\Inertia;

class ContractController extends Controller
{
    /**
     * Show the form to create a draft contract (Giai đoạn 3)
     */
    public function createDraft(Request $request)
    {
        // Require appointment_id to auto-fill guest info
        $appointmentId = $request->get('appointment_id');
        $appointment = Appointment::with(['user', 'room'])->findOrFail($appointmentId);

        // Ensure landlord owns the room
        if ($appointment->landlord_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('Landlord/Contracts/Create', [
            'appointment' => $appointment
        ]);
    }

    /**
     * Store draft contract and export PDF (Giai đoạn 3)
     */
    public function storeDraftAndExport(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);

        if ($appointment->landlord_id !== Auth::id()) {
            abort(403);
        }

        // Create contract
        $contract = Contract::create([
            'tenant_id' => $appointment->user_id,
            'room_id' => $appointment->room_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'monthly_rent' => $request->monthly_rent,
            'status' => 'awaiting_upload',
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('pdf.contract_template', [
            'contract' => $contract,
            'landlord' => Auth::user(),
            'tenant' => $appointment->user,
            'room' => $appointment->room,
        ]);

        $fileName = 'contracts/contract_' . $contract->id . '_' . time() . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());

        $contract->update(['contract_file_path' => $fileName]);

        // Trả về file PDF để tải xuống ngay
        return response()->download(storage_path('app/public/' . $fileName));
    }

    /**
     * Upload signed original contract image (Giai đoạn 5)
     */
    public function uploadSignedContract(Request $request, Contract $contract)
    {
        // Ensure landlord owns the contract's room
        if ($contract->room->boardingHouse->user_id !== Auth::id()) {
             abort(403);
        }

        $request->validate([
            'signed_image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // max 5MB
        ]);

        if ($request->hasFile('signed_image')) {
            $path = $request->file('signed_image')->store('contracts/signed', 'public');
            $contract->update([
                'signed_contract_image' => $path
            ]);

            // Fire event to activate contract and room
            event(new ContractSignedEvent($contract));

            return redirect()->back()->with('success', 'Hợp đồng đã được tải lên và kích hoạt thành công!');
        }

        return redirect()->back()->with('error', 'Không tìm thấy file ảnh.');
    }
}

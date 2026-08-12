<?php

namespace App\Http\Controllers;

use App\Repositories\ContactRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    protected ContactRepository $contactRepo;

    public function __construct(ContactRepository $contactRepo)
    {
        $this->contactRepo = $contactRepo;
    }

    /**
     * Public endpoint to submit the contact form
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $this->contactRepo->create($request->only([
            'name',
            'email',
            'phone',
            'subject',
            'message'
        ]));

        return redirect()->back()->with('success', 'Gửi liên hệ thành công! Chúng tôi sẽ phản hồi sớm nhất.');
    }

    /**
     * Admin dashboard view to list contact submissions
     */
    public function index()
    {
        $contacts = $this->contactRepo->getAll();

        return Inertia::render('Admin/Contacts/index', [
            'contacts' => $contacts
        ]);
    }

    /**
     * Admin endpoint to update the status of a contact submission
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,read,replied',
        ]);

        $contact = $this->contactRepo->findById($id);

        if (!$contact) {
            return redirect()->back()->with('error', 'Không tìm thấy liên hệ!');
        }

        $this->contactRepo->update($contact, [
            'status' => $request->status
        ]);

        return redirect()->back()->with('success', 'Cập nhật trạng thái liên hệ thành công!');
    }

    /**
     * Admin endpoint to delete a contact submission
     */
    public function delete(int $id)
    {
        $contact = $this->contactRepo->findById($id);

        if (!$contact) {
            return redirect()->back()->with('error', 'Không tìm thấy liên hệ!');
        }

        $this->contactRepo->delete($contact);

        return redirect()->back()->with('success', 'Xóa liên hệ thành công!');
    }
}

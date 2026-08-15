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

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'nullable|string|max:255',
            'email'   => [
                'nullable',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $domain = substr(strrchr($value, "@"), 1);
                        if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
                            $fail('Địa chỉ email không tồn tại (tên miền không hỗ trợ nhận thư).');
                        }
                    }
                }
            ],
            'phone'   => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ]);

        // Rate limit by IP: max 5 requests per 24 hours (86400 seconds)
        $ip = $request->ip();
        $rateLimitKey = 'contact_spam:' . $ip;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return redirect()->back()->withErrors(['email' => 'Địa chỉ IP của bạn đã gửi liên hệ quá 5 lần trong 24 giờ. Vui lòng thử lại sau!']);
        }

        // Anti-spam check by email: max 5 times per 24 hours
        if ($request->email) {
            $emailSpamCount = \DB::table('contacts')
                ->where('email', $request->email)
                ->where('created_at', '>=', now()->subDay())
                ->count();
            if ($emailSpamCount >= 5) {
                return redirect()->back()->withErrors(['email' => 'Email này đã gửi liên hệ quá 5 lần trong 24 giờ. Vui lòng thử lại sau!']);
            }
        }

        // Hit the rate limiter
        \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, 86400);

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
            'status' => 'required|string|in:pending,read,replied,spam',
        ]);

        $contact = $this->contactRepo->findById($id);

        if (!$contact) {
            return redirect()->back()->with('error', 'Không tìm thấy liên hệ!');
        }

        $this->contactRepo->update($contact, [
            'status' => $request->status
        ]);

        if ($request->status === 'read') {
            return redirect()->back(); // silent redirect
        }

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

    /**
     * Admin endpoint to send email reply to contact submission
     */
    public function reply(Request $request, int $id)
    {
        $request->validate([
            'reply_message' => 'required|string|max:5000',
        ]);

        $contact = $this->contactRepo->findById($id);

        if (!$contact) {
            return redirect()->back()->with('error', 'Không tìm thấy liên hệ!');
        }

        if (empty($contact->email)) {
            return redirect()->back()->with('error', 'Khách hàng này không cung cấp địa chỉ email để phản hồi!');
        }

        try {
            \Illuminate\Support\Facades\Mail::to($contact->email)->send(
                new \App\Mail\ContactReply($contact, $request->reply_message)
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gửi email thất bại: ' . $e->getMessage());
        }

        $this->contactRepo->update($contact, [
            'status' => 'replied'
        ]);

        return redirect()->back()->with('success', 'Gửi phản hồi qua email khách hàng thành công!');
    }
}

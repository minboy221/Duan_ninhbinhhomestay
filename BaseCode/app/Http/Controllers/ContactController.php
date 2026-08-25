<?php

namespace App\Http\Controllers;

use App\Repositories\ContactRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Contact;

class ContactController extends Controller
{
    protected ContactRepository $contactRepo;

    public function __construct(ContactRepository $contactRepo)
    {
        $this->contactRepo = $contactRepo;
    }

    public function store(Request $request)
    {
        // Honeypot check for spam bots
        if (!empty($request->input('website_hp'))) {
            return redirect()->back()->with('ticket_code', 'LH-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4)));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
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
            'phone' => 'nullable|string|max:20',
            'category' => 'nullable|string|in:general,consultation,technical,partnership',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:5',
        ], [
            'name.required' => 'Vui lòng nhập họ và tên của bạn.',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'subject.required' => 'Vui lòng nhập chủ đề liên hệ.',
            'message.required' => 'Vui lòng nhập nội dung liên hệ.',
            'message.min' => 'Nội dung liên hệ phải có ít nhất 5 ký tự.',
        ]);

        // Cooldown check: max 1 submission per 60 seconds per IP / Email
        $ipKey = 'contact_cd_ip:' . $request->ip();
        $emailKey = 'contact_cd_email:' . strtolower($request->email);

        if (
            \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($ipKey, 1) ||
            \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($emailKey, 1)
        ) {
            $secondsIp = \Illuminate\Support\Facades\RateLimiter::availableIn($ipKey);
            $secondsEmail = \Illuminate\Support\Facades\RateLimiter::availableIn($emailKey);
            $wait = max($secondsIp, $secondsEmail);
            return redirect()->back()->withErrors([
                'email' => "Vui lòng đợi {$wait} giây trước khi gửi liên hệ tiếp theo!"
            ]);
        }

        // Record attempt for 60 seconds
        \Illuminate\Support\Facades\RateLimiter::hit($ipKey, 60);
        \Illuminate\Support\Facades\RateLimiter::hit($emailKey, 60);

        $ticketCode = 'LH-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));

        $this->contactRepo->create([
            'user_id' => auth()->id(),
            'ticket_code' => $ticketCode,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'category' => $request->category ?? 'general',
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return redirect()->back()->with([
            'success' => 'Gửi liên hệ thành công! Chúng tôi sẽ phản hồi sớm nhất.',
            'ticket_code' => $ticketCode,
        ]);
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
    //hàm xử lý khiếu nại
    public function storeLockAppeal(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'phone' => 'required|string|max:20',
            'content' => 'required|string|min:10|max:1000',
        ], [
            'email.required' => 'Vui lòng nhập Email tài khoản bị khóa.',
            'email.exists' => 'Email này chưa được đăng ký trong hệ thống.',
            'phone.required' => 'Vui lòng nhập số điện thoại liên hệ.',
            'content.required' => 'Vui lòng nhập nội dung giải trình khiếu nại.',
            'content.min' => 'Nội dung giải trình phải ít nhất 10 ký tự.',
        ]);
        // Tạo bản ghi lưu vào mục Liên hệ của Admin
        Contact::create([
            'name' => 'Khiếu nại Mở khóa Tài khoản (' . $request->email . ')',
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => 'YÊU CẦU KHIẾU NẠI MỞ KHÓA TÀI KHOẢN',
            'message' => "Yêu cầu mở khóa từ User Email: {$request->email}.\nSố điện thoại: {$request->phone}.\n\nNội dung giải trình:\n" . $request->content,
            'status' => 'unread',
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Đơn khiếu nại của bạn đã được gửi tới Ban quản trị. Admin sẽ kiểm tra và phản hồi trong thời gian sớm nhất!'
        ]);
    }
}

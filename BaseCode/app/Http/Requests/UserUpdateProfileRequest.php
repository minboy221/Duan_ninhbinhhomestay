<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;
        return [
            // Bắt buộc điền đầy đủ để phục vụ Ký hợp đồng & Liên hệ
            'name' => ['required', 'string', 'max:255'],
            'phone' => $this->user()->phone
                ? ['nullable', 'string']
                : ['required', 'string', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', Rule::unique('users', 'phone')->ignore($userId)],
            'cccd_number' => [
                'required',
                'string',
                'size:12',
                'regex:/^(?!([0-9])\1{11}$)(00[1-9]|0[1-8][0-9]|09[0-6])[0-9]{9}$/',
                Rule::unique('users', 'cccd_number')->ignore($userId)
            ],
            // Tùy chọn (Không bắt buộc)
            'address' => ['nullable', 'string', 'max:255'],
            'job' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }
    /**
     * Thông báo lỗi tiếng Việt chi tiết cho từng trường
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Họ và tên là bắt buộc.',
            'email.required' => 'Địa chỉ email là bắt buộc.',
            'phone.required' => 'Số điện thoại là bắt buộc. Vui lòng nhập SĐT của bạn.',
            'phone.regex' => 'Số điện thoại không đúng định dạng Việt Nam (10 số, bắt đầu bằng 03, 05, 07, 08, 09).',
            'phone.unique' => 'Số điện thoại này đã được đăng ký bởi tài khoản khác.',
            'cccd_number.required' => 'Số Căn cước công dân (CCCD 12 số) là bắt buộc. Vui lòng nhập đủ 12 số.',
            'cccd_number.size' => 'Số căn cước công dân (CCCD) phải đúng 12 chữ số.',
            'cccd_number.regex' => 'Số CCCD không hợp lệ! 3 số đầu phải là mã tỉnh/thành phố (từ 001 đến 096) và không được nhập dãy số giả lặp lại (như 000000000000).',
            'cccd_number.unique' => 'Số CCCD này đã được đăng ký bởi một tài khoản khác.',
        ];
    }
}
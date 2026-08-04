<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'job' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'cccd_number' => ['nullable','string','size:12','regex:/^[0-9]+$/','unique:users,cccd_number,' . $userId,],
        ];

        // Cho phép gửi lên và validate nếu user chưa có số điện thoại
        if (!$this->user()->phone) {
            $rules['phone'] = [
                'required', 
                'string', 
                'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', 
                'unique:users,phone'
            ];
        }

        return $rules;
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.regex' => 'Số điện thoại không đúng định dạng Việt Nam.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng bởi tài khoản khác.',
            'cccd_number.size' => 'Số căn cước công dân (CCCD) phải đúng 12 chữ số.',
            'cccd_number.regex' => 'Số CCCD chỉ được phép chứa các chứ số.',
            'cccd_number.unique' => 'Số CCCD này đã được sử dụng bởi tài khoản khác.',
        ];
    }
}

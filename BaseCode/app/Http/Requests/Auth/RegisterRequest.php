<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'captcha' => 'required|captcha',
            'terms' => 'required|accepted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.string' => 'Họ và tên phải là chuỗi ký tự.',
            'name.max' => 'Họ và tên không vượt quá 100 ký tự.',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'captcha.required' => 'Vui lòng nhập mã xác nhận.',
            'captcha.captcha' => 'Mã xác nhận không đúng.',
            'terms.required' => 'Bạn phải đồng ý với điều khoản dịch vụ.',
            'terms.accepted' => 'Bạn phải chấp nhận các điều khoản để tiếp tục.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'họ và tên',
            'email' => 'địa chỉ email',
            'password' => 'mật khẩu',
            'captcha' => 'mã xác nhận',
            'terms' => 'điều khoản',
        ];
    }
}

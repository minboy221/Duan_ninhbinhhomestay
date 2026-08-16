<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LandlordSubscriptionRejectRequest extends FormRequest
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
            'admin_note' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'admin_note.required' => 'Vui lòng nhập lý do từ chối đơn mua gói',
            'admin_note.max' => 'lý do từ chối không được vượt quá 500 ký tự',
        ];
    }
}
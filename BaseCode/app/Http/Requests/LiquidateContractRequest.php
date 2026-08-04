<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LiquidateContractRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'deposit_handling' => 'required|in:refund_full,refund_partial,keep_deposit',
            'deposit_refund_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'deposit_handling.required' => 'Vui lòng chọn phương án xử lý tiền đặt cọc.',
            'deposit_handling.in' => 'Phương án xử lý tiền đặt cọc không hợp lệ.',
            'deposit_refund_amount.numeric' => 'Số tiền cọc hoàn trả phải là chữ số.',
            'deposit_refund_amount.min' => 'Số tiền cọc hoàn trả không được nhỏ hơn 0đ.',
            'notes.max' => 'Ghi chú quyết toán không được vượt quá 1000 ký tự.',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendContractRequest extends FormRequest
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
        $contract = $this->route('contract');
        $oldEndDate = $contract ? ($contract->end_date ? $contract->end_date->format('Y-m-d') : 'today') : 'today';

        return [
            'new_end_date' => 'required|date|after:' . $oldEndDate,
            'tenant_cccd' => 'required|string|size:12',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'new_end_date.required' => 'Vui lòng chọn ngày hết hạn mới.',
            'new_end_date.date' => 'Ngày hết hạn mới không hợp lệ.',
            'new_end_date.after' => 'Ngày hết hạn mới phải sau ngày hết hạn cũ.',
            'tenant_cccd.required' => 'Vui lòng xác nhận số CCCD của khách thuê.',
            'tenant_cccd.size' => 'Số CCCD của khách thuê phải có đúng 12 chữ số.',
        ];
    }
}


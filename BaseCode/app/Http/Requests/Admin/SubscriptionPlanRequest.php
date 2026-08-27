<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPlanRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer',
            'badge' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'features' => 'nullable|array',
        ];
    }

    public function messages():array{
        return [
            'name.required' => 'Vui lòng nhập tên gói dịch vụ',
            'price.required' => 'Vui lòng nhập giá tiền của gói',
            'price.numeric' => 'Giá tiền phải là dạng số hợp lệ',
            'price.min' => 'Giá tiền không được nhỏ hơn 0',
            'duration_days.required' => 'Vui lòng nhập số ngày sử dụng',
            'duration_days.min' => 'Số ngày sử dụng tối thiểu là 1 ngày',
        ];
    }
}

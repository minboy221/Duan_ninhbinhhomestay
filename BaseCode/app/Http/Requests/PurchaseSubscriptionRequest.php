<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseSubscriptionRequest extends FormRequest
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
            'plan_id' => 'required|exists:subscription_plans,id',
        ];
    }
    public function messages():array{
        return[
            'plan_id.required' => 'Vui lòng chọn gói dịch vụ muốn đăng ký',
            'plan_id.exists' => 'Gói dịch vụ đã chọn không tồn tại trong hệ thống',
        ];
    }
}

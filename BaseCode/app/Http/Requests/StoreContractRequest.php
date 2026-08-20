<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
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
        $oneMonthAgo = now()->subMonth()->format('Y-m-d');
        $today = now()->format('Y-m-d');
        return [
            'start_date' => ['required', 'date', 'after_or_equal:' . $oneMonthAgo],
            'end_date' => ['required', 'date', 'after:start_date', 'after_or_equal:' . $today],
            'appointment_id' => 'nullable|exists:appointments,id',
            'room_id' => 'required_without:appointment_id|exists:rooms,id',
            'tenant_id' => 'required_without:appointment_id|exists:users,id',
            'deposit' => 'required|numeric|min:0',
            'contract_file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'number_of_tenants' => 'nullable|integer|min:1',
            'entry_elec_index' => 'nullable|integer|min:0',
            'entry_water_index' => 'nullable|integer|min:0',
            'is_for_other' => 'nullable|boolean',
            'actual_tenant_name' => 'required_if:is_for_other,1,true|nullable|string|max:255',
            'actual_tenant_phone' => 'required_if:is_for_other,1,true|nullable|string|max:20',
            'actual_tenant_email' => 'nullable|email|max:255',
            'actual_tenant_cccd' => 'required_if:is_for_other,1,true|nullable|string|digits:12',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'appointment_id.required' => 'Vui lòng chọn người thuê từ lịch hẹn.',
            'appointment_id.exists' => 'Lịch hẹn được chọn không tồn tại trên hệ thống.',
            'start_date.required' => 'Vui lòng chọn ngày bắt đầu hợp đồng.',
            'start_date.date' => 'Ngày bắt đầu hợp đồng không hợp lệ.',
            'end_date.required' => 'Vui lòng chọn ngày kết thúc hợp đồng.',
            'end_date.date' => 'Ngày kết thúc hợp đồng không hợp lệ.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'deposit.required' => 'Vui lòng nhập tiền đặt cọc.',
            'deposit.numeric' => 'Tiền đặt cọc phải là chữ số.',
            'deposit.min' => 'Tiền đặt cọc không được nhỏ hơn 0đ.',
            'contract_file.required' => 'Vui lòng tải lên file/ảnh chụp hợp đồng giấy đã ký.',
            'contract_file.file' => 'Tệp tải lên không hợp lệ.',
            'contract_file.mimes' => 'Hệ thống chỉ hỗ trợ định dạng ảnh (jpg, jpeg, png) hoặc file PDF.',
            'contract_file.max' => 'Dung lượng file tải lên không được vượt quá 10MB.',
        ];
    }
}

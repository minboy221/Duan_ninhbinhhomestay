<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reportable_type' => 'required|string|in:Post,Property,Contract,Invoice,Room,BoardingHouse,User',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|max:255',
            'resolve_type' => 'required|string|in:direct,system',
            //trường phân biệt hình thức báo cáo
            //chỉ bắt buộc mô tả và ảnh bằng chứng khi gửi tới admin
            'description' => 'required_if:resolve_type,system|nullable|string|min:5|max:1000',
            'evidence_images' => 'required_if:resolve_type,system|nullable|array|max:5',
            'evidence_images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng chọn lý do báo cáo.',
            'resolve_type.required' => 'Vui lòng chọn hình thức xử lý khiếu nại.',
            'description.required_if' => 'Bạn đã chọn báo cáo lên hệ thống, vui lòng nhập mô tả chi tiết.',
            'description.min' => 'Mô tả báo cáo phải dài tối thiểu 5 ký tự.',
            'evidence_images.required_if' => 'Bạn đã chọn báo cáo lên hệ thống, vui lòng tải ảnh bằng chứng lên.',
            'evidence_images.max' => 'Bạn chỉ được tải lên tối đa 5 ảnh bằng chứng.',
            'evidence_images.*.image' => 'Bằng chứng tải lên phải là định dạng hình ảnh.',
            'evidence_images.*.mimes' => 'Hình ảnh bằng chứng chỉ hỗ trợ định dạng jpeg, png, jpg.',
            'evidence_images.*.max' => 'Dung lượng mỗi ảnh bằng chứng không được vượt quá 2MB.',
        ];
    }
}

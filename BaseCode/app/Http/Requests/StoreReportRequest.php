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
            'description' => 'required|string|min:5|max:1000',
            'evidence_images' => 'required|array|min:1|max:5',
            'evidence_images.*' => 'image|mimes:jpeg,png,jpg,webp,heic,heif|max:15360',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng chọn lý do báo cáo.',
            'resolve_type.required' => 'Vui lòng chọn hình thức xử lý khiếu nại.',
            'description.required' => 'Vui lòng nhập mô tả chi tiết sự cố/vi phạm.',
            'description.min' => 'Mô tả báo cáo phải dài tối thiểu 5 ký tự.',
            'evidence_images.required' => 'Vui lòng tải lên ít nhất 1 hình ảnh bằng chứng.',
            'evidence_images.min' => 'Vui lòng tải lên ít nhất 1 hình ảnh bằng chứng.',
            'evidence_images.max' => 'Bạn chỉ được tải lên tối đa 5 ảnh bằng chứng.',
            'evidence_images.*.image' => 'Bằng chứng tải lên phải là định dạng hình ảnh.',
            'evidence_images.*.mimes' => 'Hình ảnh bằng chứng hỗ trợ định dạng jpeg, png, jpg, webp, heic, heif.',
            'evidence_images.*.max' => 'Dung lượng mỗi ảnh bằng chứng không được vượt quá 15MB.',
        ];
    }
}

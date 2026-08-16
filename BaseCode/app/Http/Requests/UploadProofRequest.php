<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProofRequest extends FormRequest
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
            'proof_image' => ['required|image|mimes:jpeg,png,jpg,webp|max:5120'],
        ];
    }
    public function messages(): array
    {
        return [
            'proof_image.required' => 'Vui lòng chọn ảnh hóa đơn chuyển khoản.',
            'proof_image.image' => 'Tệp tải lên phải là định dạng hình ảnh.',
            'proof_image.mimes' => 'Ảnh hóa đơn chỉ chấp nhận đuôi: jpeg, png, jpg hoặc webp.',
            'proof_image.max' => 'Dung lượng ảnh tối đa không được vượt quá 5MB.',
        ];
    }
}

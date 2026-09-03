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
            'proof_image' => ['required', 'mimes:jpeg,png,jpg,webp,heic,heif', 'max:15360'],
        ];
    }
    public function messages(): array
    {
        return [
            'proof_image.required' => 'Vui lòng chọn ảnh hóa đơn chuyển khoản.',
            'proof_image.mimes' => 'Ảnh hóa đơn chỉ chấp nhận định dạng: jpeg, png, jpg, webp, heic hoặc heif.',
            'proof_image.max' => 'Dung lượng ảnh tối đa không được vượt quá 15MB.',
        ];
    }
}

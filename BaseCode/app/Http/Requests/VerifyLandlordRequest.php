<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyLandlordRequest extends FormRequest
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
            'phone' => 'required|string|max:10',
            'id_card_number' => 'nullable|string|max:20',
            'id_card_front' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'id_card_back' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'face_auth_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'is_face_matched' => 'required|boolean',
            'property_name' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'address_detail' => 'required|string|max:255',
            'contract_images' => 'required|array',
            'contract_images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'room_images' => 'required|array',
            'room_images.*' => 'file|mimes:jpeg,png,jpg,webp,mp4,mov,avi|max:20480',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Vui lòng nhập :attribute.',
            'string' => ':attribute phải là chuỗi ký tự.',
            'max' => ':attribute không được vượt quá :max ký tự.',
            'image' => ':attribute phải là hình ảnh.',
            'mimes' => ':attribute không hợp lệ. Vui lòng tải lên định dạng (jpeg, png, jpg, webp, mp4, mov, avi).',
            'contract_images.*.mimes' => 'Ảnh hợp đồng không hợp lệ (chỉ hỗ trợ jpeg, png, jpg).',
            'contract_images.*.max' => 'Ảnh hợp đồng không được vượt quá 5MB.',
            'room_images.*.mimes' => 'Video/Ảnh phòng không hợp lệ.',
            'room_images.*.max' => 'Video/Ảnh phòng không được vượt quá 20MB.',
            'id_card_front.max' => 'Ảnh CCCD mặt trước không được vượt quá 5MB.',
            'id_card_back.max' => 'Ảnh CCCD mặt sau không được vượt quá 5MB.',
            'face_auth_image.max' => 'Ảnh khuôn mặt không được vượt quá 5MB.',
            'is_face_matched.boolean' => 'Dữ liệu xác thực khuôn mặt không hợp lệ.',
            'is_face_matched.required' => 'Vui lòng thực hiện xác minh khuôn mặt.',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'Số điện thoại',
            'id_card_number' => 'Số CCCD',
            'id_card_front' => 'Ảnh CCCD mặt trước',
            'id_card_back' => 'Ảnh CCCD mặt sau',
            'face_auth_image' => 'Ảnh khuôn mặt',
            'property_name' => 'Tên nhà trọ',
            'district' => 'Khu vực',
            'address_detail' => 'Địa chỉ chi tiết',
            'contract_images' => 'Ảnh hợp đồng',
            'room_images' => 'Video/Ảnh phòng',
        ];
    }
}

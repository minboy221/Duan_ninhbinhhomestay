<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoardingHouseRequest extends FormRequest
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
            'district' => 'required|string|max:255',
            'address_detail' => 'required|string|max:500',
            'directions_guide' => 'nullable|string|max:1000',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'room_images' => 'required|array',
            'room_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Tên cơ sở trọ không được để trống.',
            'district.required' => 'Khu vực không được để trống.',
            'addres_detail.required' => 'Địa chỉ chi tiết không được để trống.',
            'latitude.required' => 'Vui lòng xác định vĩ độ GPS.',
            'longitude.required' => 'Vui lòng xác kinh độ GPS.',
            'room_images.required' => 'Vui lòng tải lên ít nhất một ảnh của cơ sở',
            'room_images.*.image' => 'File tải lên bắt buộc phải là hình ảnh',
            'room_images.*.mimes' => 'Ảnh chỉ chấp nhận các định dạng: jpeg, png, jpg, gif.',
            'room_images.*.max' => 'Dung lượng ảnh tối đa là 5MB.',
        ];
    }
}

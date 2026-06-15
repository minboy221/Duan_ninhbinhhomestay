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
}

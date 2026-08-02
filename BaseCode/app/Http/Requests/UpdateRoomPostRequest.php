<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomPostRequest extends FormRequest
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
        $rules = [
            'action' => 'required|string|in:draft,publish',
            'room_id' => 'required|exists:rooms,id',
            'title' => 'required|string|min:10|max:255',
            'current_people' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'existing_images' => 'nullable|array', //mảng các link ảnh sẽ giữ lại
            'address' => 'nullable|string',
            'latitude'=> 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];

        if ($this->input('action') === 'publish') {
            $rules['description'] = 'required|string|min:20';
            //nếu không giữ ảnh cũ thì bắt buộc phải chọn ảnh mới tải lên
            if (empty($this->input('existing_images'))) {
                $rules['images'] = 'required|array|min:1';
            } else {
                $rules['images'] = 'nullable|array';
            }
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg,webp|max:2048';
        } else {
            $rules['description'] = 'nullable|string';
            $rules['images'] = 'nullable|array';
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg,webp|max:2048';
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Vui lòng chọn căn phòng trọ cần tiếp thị.',
            'title.required' => 'Tiêu đề tin đăng không được bỏ trống.',
            'title.min' => 'Tiêu đề tin đăng phải từ 10 ký tự trở lên.',
            'description.required' => 'Nội dung mô tả phòng trọ là bắt buộc khi Đăng tin thương mại.',
            'description.min' => 'Nội dung mô tả phòng trọ phải từ 20 ký tự trở lên.',
            'images.required' => 'Vui lòng tải lên ảnh chụp thực tế nếu bạn đã xóa sạch bộ ảnh cũ.',
        ];
    }
}

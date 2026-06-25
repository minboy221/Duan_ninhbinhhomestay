<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoomPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;  //cho phép mọi user đăng nhập sử dụng
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // 1. Cấu hình các quy tắc cơ bản (Bắt buộc nhập dù là lưu nháp hay đăng tin)
        $rules = [
            'room_id' => [
                'required',
                'exists:rooms,id',
                function ($attribute, $value, $fail) {
                    $room = Room::find($value);
                    // Sửa nhẹ lỗi chính tả "đămg tin" thành "đăng tin" cho chuyên nghiệp nhé bạn
                    if ($room && $room->status !== 'available') {
                        $fail('Căn phòng này hiện không ở trạng thái trống để đăng tin.');
                    }
                },
            ],
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ];

        // 2. Kiểm tra hành động từ phía Frontend gửi lên
        // Nếu là 'publish' (Gửi duyệt công khai) -> Bắt buộc phải có ảnh thực tế
        if ($this->input('action') === 'publish') {
            $rules['images'] = 'required|array|min:1';
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg,webp|max:2048';
        } else {
            // Nếu là 'draft' (Lưu bản nháp) -> Không bắt buộc up ảnh ngay lúc này
            $rules['images'] = 'nullable|array';
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg,webp|max:2048';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'vui lòng chọn một căn phòng cụ thể từ sơ đồ',
            'title.required' => 'tiêu đề bài đăng không được để trống',
            'description.required' => 'vui lòng nhập mô tả chi tiết cho phòng trọ',
            'images.required' => 'bạn phải tải lên ít nhất một tấm hình ảnh thực tế',
            'images.*.max' => 'Dung lượng mỗi ảnh không được vượt quá 2MB',
        ];
    }
}
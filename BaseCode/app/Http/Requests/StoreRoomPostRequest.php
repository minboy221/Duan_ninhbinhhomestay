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
        return true;  //cho phép chạy qua rào chắn quyền
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
            'action' => 'required|string|in:draft,publish',
            'room_id' => 'required|exists:rooms,id',
            'title' => 'required|string|min:10|max:255',
            'current_people' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];

        // 2. Kiểm tra hành động từ phía Frontend gửi lên
        // Nếu là 'publish' (Gửi duyệt công khai) -> Bắt buộc phải có ảnh thực tế
        if ($this->input('action') === 'publish') {
            $rules['description'] = 'required|string|min:20';
            $rules['images'] = 'required|array|min:3'; //bắt buộc tải lên ít nhất 3 ảnh
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg,webp|max:2048';
        } else {
            // Nếu là 'draft' (Lưu bản nháp) -> Không bắt buộc up ảnh ngay lúc này
            $rules['description'] = 'nullable|string';
            $rules['images'] = 'nullable|array';
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg,webp|max:2048';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Vui lòng chọn một căn phòng để đăng tin.',
            'room_id.exists' => 'Căn phòng trọ đã chọn không hợp lệ.',
            'title.required' => 'Tiêu đề tin đăng không được để trống.',
            'title.min' => 'Tiêu đề tin đăng phải có ít nhất 10 ký tự.',
            'description.required' => 'Bạn phải nhập nội dung mô tả phòng trọ mới được phép Đăng tin.',
            'description.min' => 'Nội dung mô tả quá ngắn (Tối thiểu phải 20 ký tự).',
            'images.required' => 'Bạn bắt buộc phải đăng tải ít nhất 1 hình ảnh thực tế của căn phòng.',
            'images.min' => 'Vui lòng chọn ít nhất 1 hình ảnh chụp phòng.',
            'images.*.image' => 'File tải lên phải là định dạng hình ảnh.',
            'images.*.max' => 'Dung lượng ảnh quá lớn (Tối đa 2MB mỗi file).'
        ];
    }
}
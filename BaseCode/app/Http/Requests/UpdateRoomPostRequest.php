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
            'room_id' => [
                'required',
                'exists:rooms,id',
                function ($attribute, $value, $fail) {
                    $room = \App\Models\Room::find($value);
                    if ($room) {
                        $postId = $this->route('id'); //lấy id bài viết từ router Url
                        $post = \App\Models\RoomPost::find($postId);
                        if ($post && $post->room_id == $value) {
                            return;
                        }
                        if ($room->status !== 'available') {
                            $fail("Căn phòng này hiện không ở trạng thái trống để đăng tin");
                        }
                    }
                }
            ],

            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'existing_images' => 'nullable|array',
            'existing_images.*' => 'string',
            //khi sửa tin, ảnh sẽ là tuỳ chọn nullable vì hệ thống sẽ giữ lại ảnh cũ nếu không chọn file mới
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
        //phần đăng tin publish ảnh cũ sẽ được giữ lại và ảnh mới tải lên >=1
        if ($this->input('action') === 'publish') {
            $rules['images'] = [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    $existingImages = $this->input('existingImages') ?? [];
                    $newImagesCount = is_array($value) ? count($value) : 0;

                    if (count($existingImages) + $newImagesCount === 0) {
                        $fail('Bạn phải tải lên hoặc giữ lại ít nhất 1 hình ảnh thực tế cho bài đăng');
                    }
                }
            ];
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'tiêu đề bài viết không được để trống',
            'title.max' => 'tiêu đề bài viết không vượt quá 255 ký tự',
            'description.required' => 'Nội dung mô tả chi tiết không được để trống',
            'images.array' => 'Dữ liệu hình ảnh tải lên không đúng định dạng',
            'images.*.image' => 'Tệp tải lên phải là hình ảnh',
            'images.*.mimes' => 'Hệ thống chỉ hỗ chị ảnh định dạng:jpeg,png,jpg',
            'images.*.max' => 'Dung lượng mỗi ảnh bổ sung không quá 2MB',
        ];
    }
}

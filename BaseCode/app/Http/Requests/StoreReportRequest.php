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
            'reportable_type' => 'required|string|in:Post,Property,Contract,Invoice',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'evidence_images' => 'nullable|array|max:5',
            'evidence_images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}

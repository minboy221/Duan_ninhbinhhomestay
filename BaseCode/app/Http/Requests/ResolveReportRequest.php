<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveReportRequest extends FormRequest
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
            'response_note' => 'nullable|string|max:1000',
            'response_evidence' => 'nullable|array|max:5',
            'response_evidence.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'action' => 'required|in:target_resolve,reporter_accept,escalate_admin',
        ];
    }
}

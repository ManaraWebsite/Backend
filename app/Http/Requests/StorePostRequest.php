<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title_ar' => ['required', 'string', 'max:255'],
            'content_ar' => ['required', 'string'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'content_en' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'status' => ['required', 'in:draft,published'],
            'form_link' => ['nullable', 'url'],
        ];
    }
}

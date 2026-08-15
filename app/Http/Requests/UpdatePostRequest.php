<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
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
        $postId = $this->route('post')->id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'cover_image' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'published'])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be either draft or published.',
            'cover_image.image' => 'The cover image must be a valid image file.',
            'cover_image.max' => 'The cover image may not be greater than 2048 kilobytes.',
        ];
    }
}

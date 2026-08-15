<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],

            'fields' => ['required', 'array', 'min:1'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::in(['text', 'email', 'number', 'date', 'select', 'radio', 'checkbox', 'textarea'])],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.options.*' => ['string'],
            'fields.*.is_required' => ['sometimes', 'boolean'],
            'fields.*.order' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'fields.required' => 'A form must have at least one field.',
            'fields.*.type.in' => 'Invalid field type selected.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Require options only for choice-based field types
        $this->validate([
            'fields' => 'array',
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->input('fields', []) as $index => $field) {
                $needsOptions = in_array($field['type'] ?? null, ['select', 'radio', 'checkbox']);
                if ($needsOptions && empty($field['options'])) {
                    $validator->errors()->add("fields.{$index}.options", 'This field type requires at least one option.');
                }
            }
        });
    }
}

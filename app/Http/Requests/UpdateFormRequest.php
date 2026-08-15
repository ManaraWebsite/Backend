<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormRequest extends FormRequest
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
        $formId = $this->route('form')->id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('forms', 'slug')->ignore($formId),
            ],
            'is_active' => ['sometimes', 'boolean'],

            'fields' => ['sometimes', 'array', 'min:1'],
            'fields.*.id' => [
                'nullable',
                'integer',
                Rule::exists('form_fields', 'id')->where('form_id', $formId),
            ],
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
            'fields.*.id.exists' => 'One of the fields does not belong to this form.',
        ];
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

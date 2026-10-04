<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SubmitFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $form = $this->route('form');

        // Block submissions to inactive forms at the authorization stage
        return $form && $form->is_active;;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $form = $this->route('form')->load('fields');
        $rules = [];

        foreach ($form->fields as $field) {
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            $fieldRules[] = match ($field->type) {
                'email' => 'email',
                'number' => 'numeric',
                'date' => 'date',
                in_array($field->type, ['select', 'radio']) && in_array('Other', $field->options ?? [])
                => 'string', // has an "Other" escape hatch — accept free text
                in_array($field->type, ['select', 'radio'])
                => Rule::in($field->options ?? []),
                'checkbox' => 'array', // multi-select, validated per-item below
                'file' => File::types(['pdf'])->max(5 * 1024), // kilobytes
                default => 'string',
            };

            $rules["answers.{$field->id}"] = $fieldRules;

            if ($field->type === 'checkbox') {
                $rules["answers.{$field->id}.*"] = Rule::in($field->options ?? []);
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'answers.*.required' => 'This field is required.',
            'answers.*.in' => 'The selected value is not a valid option.',
            'answers.*.file' => 'This field must be a file.',
            'answers.*.mimes' => 'The file must be a PDF.',
            'answers.*.max' => 'The file may not be greater than 5 MB.',
            // Also what PHP's own upload_max_filesize rejection surfaces as.
            'answers.*.uploaded' => 'The file failed to upload. It may be too large.',
        ];
    }
}

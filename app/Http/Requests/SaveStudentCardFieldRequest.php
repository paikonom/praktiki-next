<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStudentCardFieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view-admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:2000'],
            'section' => ['nullable', 'string', 'max:255'],
            'fundings' => ['required', 'array', 'min:1'],
            'fundings.*' => ['required', Rule::in(['all', 'Espa', 'InternshipProvider', 'Institution'])],
            'visible_to_roles' => ['required', 'array', 'min:1'],
            'visible_to_roles.*' => ['required', Rule::in(['all', 'member', 'student', 'provider', 'scientific_supervisor'])],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['required', Rule::in([0, 1])],
        ];
    }
}

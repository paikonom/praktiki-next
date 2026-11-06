<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveScientificSupervisorRequest extends FormRequest
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
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'fName' => ['required', 'string', 'max:100'],
            'lName' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:100'],
            'thl' => ['nullable', 'string', 'max:50'],
            'apofash_tmhmatos' => ['nullable', 'string', 'max:100'],
            'syneleush_tmhmatos' => ['nullable', 'string', 'max:100'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}

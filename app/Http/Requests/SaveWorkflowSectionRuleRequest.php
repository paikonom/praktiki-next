<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveWorkflowSectionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-admin') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $workflowSectionRule = $this->route('workflow_section_rule');

        return [
            'funding_code' => [
                'required',
                Rule::in(['all', 'Espa', 'Institution', 'InternshipProvider']),
                Rule::unique('workflow_section_rules', 'funding_code')->where(fn ($query) => $query
                    ->where('section', $this->string('section'))
                    ->where('role', $this->string('role')))->ignore($workflowSectionRule),
            ],
            'section' => ['required', Rule::in(['A', 'B', 'G', 'D'])],
            'role' => ['required', Rule::in(['student', 'provider', 'scientific_supervisor', 'member'])],
            'available_after' => ['required', Rule::in(['always', 'internship_start', 'internship_end'])],
            'requires_previous_section' => ['required', 'boolean'],
        ];
    }
}

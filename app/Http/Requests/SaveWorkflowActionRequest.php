<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveWorkflowActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-admin') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $workflowAction = $this->route('workflow_action');

        return [
            'funding_code' => ['required', Rule::in(['all', 'Espa', 'Institution', 'InternshipProvider'])],
            'section' => ['required', Rule::in(['A', 'B', 'G', 'D'])],
            'sort_order' => ['required', 'numeric', 'min:0', 'max:999'],
            'code' => ['required', 'string', 'max:100', Rule::unique('workflow_actions')->where(fn ($query) => $query->where('funding_code', $this->string('funding_code')))->ignore($workflowAction)],
            'title' => ['required', 'string', 'max:255'],
            'responsible_role' => ['required', Rule::in(['student', 'provider', 'scientific_supervisor', 'member'])],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['string', 'distinct', Rule::exists('workflow_actions', 'code')],
            'source_type' => ['required', Rule::in(['action', 'document', 'apografiko', 'student_questionnaire', 'provider_questionnaire'])],
            'source_key' => ['required', 'string', 'max:100'],
            'allowed_statuses' => ['required', 'array', 'min:1'],
            'allowed_statuses.*' => ['string', 'distinct'],
            'completed_statuses' => ['nullable', 'array'],
            'completed_statuses.*' => ['string', 'distinct', Rule::in($this->input('allowed_statuses', []))],
            'counts_for_section' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
        ];
    }
}

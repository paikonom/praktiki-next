<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveDocumentTypeRequest extends FormRequest
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
        $id = $this->route('documentType')?->id;

        return [
            'code' => ['required', 'alpha_dash', 'max:100', 'unique:document_types,code,'.$id],
            'title' => ['required', 'string', 'max:500'],
            'instructions' => ['nullable', 'string'],
            'max_size_mb' => ['required', 'integer', 'between:1,100'],
            'active' => ['boolean'],
            'sort_order' => ['required', 'decimal:0,2', 'between:0,9999.99'],
            'fundings' => ['required', 'array', 'min:1'],
            'fundings.*' => ['in:Espa,Institution,InternshipProvider'],
            'view_roles' => ['required', 'array', 'min:1'],
            'view_roles.*' => ['in:student,provider,scientific_supervisor,member'],
            'upload_roles' => ['array'],
            'upload_roles.*' => ['in:student,provider,scientific_supervisor,member'],
        ];
    }
}

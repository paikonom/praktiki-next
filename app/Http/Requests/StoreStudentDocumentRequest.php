<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreStudentDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $documentType = $this->route('documentType');

        if (! $documentType instanceof DocumentType) {
            $documentType = DocumentType::findOrFail($documentType);
        }

        return ['file' => ['required', File::types(['pdf'])->max("{$documentType->max_size_mb}mb")]];
    }
}

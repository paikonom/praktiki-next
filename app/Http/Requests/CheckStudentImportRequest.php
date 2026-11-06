<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckStudentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->view_only;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'phase_id' => ['required', 'integer', 'exists:praktiki_phases,id'],
            'students' => ['required', 'string', 'max:100000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'department_id.required' => 'Επιλέξτε τμήμα.',
            'phase_id.required' => 'Επιλέξτε φάση.',
            'students.required' => 'Επικολλήστε τουλάχιστον μία γραμμή από το Excel.',
            'students.max' => 'Το κείμενο είναι πολύ μεγάλο για έναν έλεγχο.',
        ];
    }
}

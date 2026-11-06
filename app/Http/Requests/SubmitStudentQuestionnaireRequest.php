<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Support\StudentQuestionnaireDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitStudentQuestionnaireRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['submission' => 'final']);
    }

    public function authorize(): bool
    {
        $student = $this->user('student');

        return $student instanceof Student
            && ! $this->student()->questionnaire?->isSubmitted()
            && app(StudentQuestionnaireDefinition::class)->available($this->student());
    }

    public function rules(): array
    {
        $draft = $this->input('submission') === 'draft';
        $rules = [
            'submission' => ['required', Rule::in(['draft', 'final'])],
            'answers' => [$draft ? 'nullable' : 'required', 'array'],
        ];

        foreach ($this->questions() as $question) {
            $rules['answers.'.$question->code] = $question->type === 'radio'
                ? [$draft ? 'nullable' : 'required', 'string', Rule::in($question->options)]
                : ['nullable', 'string', 'max:10000'];
        }

        return $rules;
    }

    public function isDraft(): bool
    {
        return $this->validated('submission') === 'draft';
    }

    public function attributes(): array
    {
        return $this->questions()
            ->mapWithKeys(fn ($question) => ['answers.'.$question->code => $question->label])
            ->all();
    }

    public function student(): Student
    {
        /** @var Student $student */
        $student = $this->user('student');

        return $student->loadMissing(['department', 'phase.praktiki.funding', 'actions', 'questionnaire', 'documents.type']);
    }

    public function questions()
    {
        $student = $this->student();

        return collect(app(StudentQuestionnaireDefinition::class)->sections(
            $student,
            $student->questionnaire?->version,
        ))
            ->pluck('questions')
            ->flatten();
    }
}

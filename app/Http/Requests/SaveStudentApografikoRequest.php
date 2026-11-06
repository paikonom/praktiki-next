<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Support\StudentApografikoDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStudentApografikoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['submission' => 'final']);
    }

    public function authorize(): bool
    {
        $student = $this->user('student');
        $type = $this->route('type');

        if (! $student instanceof Student || ! in_array($type, ['input', 'output'], true)) {
            return false;
        }

        $student->loadMissing(['phase.praktiki.funding', 'documents.type', 'apografika']);
        $apografiko = $student->apografika->firstWhere('type', $type);

        return ! $apografiko?->isFinal()
            && app(StudentApografikoDefinition::class)->unavailableReason($student, $type) === null;
    }

    public function rules(): array
    {
        $draft = $this->input('submission') === 'draft';
        $type = $this->route('type');
        $rules = [
            'submission' => ['required', Rule::in(['draft', 'final'])],
            'answers' => ['nullable', 'array'],
        ];

        if ($type !== 'input') {
            return $rules;
        }

        $definition = app(StudentApografikoDefinition::class);

        $rules['answers.education_level'] = [
            $draft ? 'nullable' : 'required',
            'string',
            Rule::in($definition->educationQuestions()->keys()->map('strval')->all()),
        ];
        $rules['answers.indicators'] = ['nullable', 'array'];

        foreach ($definition->indicatorQuestions()->keys() as $id) {
            $rules["answers.indicators.{$id}"] = [
                $draft ? 'nullable' : 'required',
                'string',
                Rule::in(array_values(config('praktiki_apografika.answers'))),
            ];
        }

        return $rules;
    }

    public function isDraft(): bool
    {
        return $this->validated('submission') === 'draft';
    }

    /** @return array<string, mixed> */
    public function submittedAnswers(): array
    {
        return $this->validated('answers') ?? [];
    }

    public function attributes(): array
    {
        return [
            'answers.education_level' => 'μορφωτικό επίπεδο',
            ...app(StudentApografikoDefinition::class)->indicatorQuestions()
                ->mapWithKeys(fn ($question, int $id): array => ["answers.indicators.{$id}" => $question->perigrafi])
                ->all(),
        ];
    }
}

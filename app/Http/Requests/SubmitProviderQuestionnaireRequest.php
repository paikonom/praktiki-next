<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Support\ProviderQuestionnaireDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitProviderQuestionnaireRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['submission' => 'final']);
    }

    public function authorize(): bool
    {
        $student = $this->student();

        return ! $student->providerQuestionnaire?->isSubmitted()
            && app(ProviderQuestionnaireDefinition::class)->unavailableReason($student) === null;
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
                : [$draft || $question->code !== 'comm' ? 'nullable' : 'required', 'string', 'max:10000'];
        }

        return $rules;
    }

    public function isDraft(): bool
    {
        return $this->validated('submission') === 'draft';
    }

    public function attributes(): array
    {
        return $this->questions()->mapWithKeys(fn ($question) => ['answers.'.$question->code => $question->label])->all();
    }

    public function student(): Student
    {
        /** @var Student $student */
        $student = $this->user('provider');

        return $student->loadMissing(['department', 'provider', 'actions', 'providerQuestionnaire']);
    }

    public function questions()
    {
        return collect(app(ProviderQuestionnaireDefinition::class)->sections($this->student(), 1))
            ->pluck('questions')->flatten();
    }
}

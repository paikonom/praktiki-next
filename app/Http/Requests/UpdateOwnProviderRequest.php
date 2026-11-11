<?php

namespace App\Http\Requests;

use App\Models\StudentAction;
use App\Models\StudentCardField;
use App\Support\FormFields;
use App\Support\RescomReferenceData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateOwnProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('student') !== null
            && ! $this->user('student')->isCancelled()
            && ! $this->user('student')->actionHasStatus('stoix_forea', [StudentAction::STATUS_SUBMITTED, StudentAction::STATUS_APPROVED]);
    }

    public function rules(): array
    {
        $rescomReferenceData = app(RescomReferenceData::class);

        $rules = [
            'f_epwnymia' => ['required', 'string', 'max:150'],
            'f_nomikhMorfh_rescom' => ['required', Rule::in(array_keys(config('praktiki_fields.select_options.f_nomikhMorfh_rescom')))],
            'f_doy_rescom' => ['required', Rule::in(array_keys($rescomReferenceData->options('doys')))],
            'dhm_idiwt_rescom' => ['required', Rule::in([1, 2])],
            'f_proeleush_rescom' => ['required', Rule::in([1, 2, 3])],
            'f_afm' => ['required', 'regex:/^([0-9]{7}|[0-9]{9})$/'],
            'f_polh_rescom' => ['required', Rule::in(array_keys($rescomReferenceData->options('cities')))],
            'f_xwra_rescom' => ['required', Rule::in(array_keys($rescomReferenceData->options('countries')))],
            'f_odos' => ['required', 'string', 'max:100'],
            'f_tk' => ['required', 'regex:/^[0-9]{5}$/'],
            'f_perifereia' => ['required', Rule::in(array_keys(config('praktiki_fields.select_options.f_perifereia')))],
            'f_thl' => ['required', 'string', 'max:50'],
            'f_email' => ['required', 'email:rfc', 'max:100'],
            'f_ekprFname' => ['required', 'string', 'max:200'],
            'f_ekprLname' => ['required', 'string', 'max:200'],
            'f_ekprIdiothta' => ['nullable', 'string', 'max:60'],
            'f_ektPrakt_polh_rescom' => ['required', Rule::in(array_keys($rescomReferenceData->options('cities')))],
            'f_ektPrakt_odos' => ['required', 'string', 'max:200'],
            'f_ektPrakt_tk' => ['required', 'regex:/^[0-9]{5}$/'],
            'f_epopt_foreaFname' => ['required', 'string', 'max:200'],
            'f_epopt_foreaLname' => ['required', 'string', 'max:200'],
            'f_epopt_email' => ['required', 'email:rfc', 'max:50'],
            'f_epopt_foreaThesh' => ['required', 'string', 'max:65535'],
            'f_epopt_foreaKatartish' => ['required', 'string', 'max:65535'],
            'f_antikeim_Praktikhs' => ['sometimes', 'required', 'string', 'max:65535'],
            'f_apotel_Praktikhs' => ['sometimes', 'required', 'string', 'max:65535'],
        ];

        if ($this->isDraft()) {
            $rules = collect($rules)->map(fn (array $fieldRules): array => collect($fieldRules)
                ->map(fn (mixed $rule): mixed => $rule === 'required' ? 'nullable' : $rule)
                ->all()
            )->all();
        }

        $configuredFields = app(FormFields::class)->provider($this->fundingCode());
        $activeFields = StudentCardField::query()
            ->forCardSection('provider')
            ->active()
            ->visibleFor($this->fundingCode(), 'student')
            ->whereIn('field', $configuredFields)
            ->pluck('field')
            ->all();

        return Arr::only($rules, $activeFields);
    }

    public function messages(): array
    {
        return [
            'f_afm.regex' => 'Το ΑΦΜ του φορέα πρέπει να έχει 7 ή 9 ψηφία.',
            'f_tk.regex' => 'Ο ταχυδρομικός κώδικας του φορέα πρέπει να έχει ακριβώς 5 ψηφία.',
            'f_ektPrakt_tk.regex' => 'Ο ταχυδρομικός κώδικας του τόπου εκτέλεσης πρέπει να έχει ακριβώς 5 ψηφία.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect(config('praktiki_fields.uppercase_fields'))
            ->filter(fn (string $field): bool => $this->has($field) && is_string($this->input($field)))
            ->mapWithKeys(fn (string $field): array => [$field => mb_strtoupper($this->input($field))])
            ->all());
    }

    private function fundingCode(): string
    {
        $student = $this->user('student');
        $student->loadMissing('phase.praktiki.funding');

        return $student->fundingCode();
    }

    public function isDraft(): bool
    {
        return $this->input('submission') === 'draft';
    }
}

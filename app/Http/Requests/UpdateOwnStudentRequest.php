<?php

namespace App\Http\Requests;

use App\Models\StudentAction;
use App\Models\StudentCardField;
use App\Support\FormFields;
use App\Support\RescomReferenceData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateOwnStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('student') !== null
            && ! $this->user('student')->actionHasStatus('stoix_student', [StudentAction::STATUS_SUBMITTED, StudentAction::STATUS_APPROVED]);
    }

    public function rules(): array
    {
        $rescomReferenceData = app(RescomReferenceData::class);

        $rules = [
            'fName' => ['required', 'string', 'max:100'],
            'lName' => ['required', 'string', 'max:100'],
            'amka' => ['required', 'regex:/^[0-9]{11}$/'],
            'afm' => ['required', 'regex:/^([0-9]{7}|[0-9]{9})$/'],
            'id_group' => ['required', 'integer'],
            'ama' => ['required', 'regex:/^([0-9]{7}|[0-9]{9})$/'],
            'fylo_rescom' => ['required', Rule::in(array_keys(config('praktiki_fields.select_options.fylo_rescom')))],
            'iban' => ['required', 'regex:/^GR[0-9]{25}$/'],
            'adt' => ['required', 'string', 'max:50'],
            'adt_ek_arxh' => ['required', 'string', 'max:100'],
            'adt_ek_date' => ['required', 'date'],
            'fatFName' => ['required', 'string', 'max:100'],
            'fatLName' => ['required', 'string', 'max:100'],
            'momFName' => ['required', 'string', 'max:100'],
            'momLName' => ['required', 'string', 'max:100'],
            'katEkp_rescom' => ['required', Rule::in(array_keys(config('praktiki_fields.select_options.katEkp_rescom')))],
            'oikKat' => ['required', Rule::in(array_keys(config('praktiki_fields.select_options.oikKat')))],
            'syzName' => ['required', 'string', 'max:100'],
            'numChild' => ['required', 'integer', 'between:0,255'],
            'doy_rescom' => ['required', Rule::in(array_keys($rescomReferenceData->options('doys')))],
            'dob' => ['required', 'date'],
            'placeDob' => ['required', 'string', 'max:100'],
            'ithagen' => ['required', 'string', 'max:100'],
            'xwra_rescom' => ['required', Rule::in(array_keys($rescomReferenceData->options('countries')))],
            'address_polh_rescom' => ['required', Rule::in(array_keys($rescomReferenceData->options('cities')))],
            'address_odos' => ['required', 'string', 'max:100'],
            'address_odos_polh' => ['required', 'string', 'max:100'],
            'address_tk' => ['required', 'regex:/^[0-9]{5}$/'],
            'workDaysDescription' => ['required', 'string', 'max:200'],
            'numMhnes' => ['required', Rule::in(array_keys(config('praktiki_fields.select_options.numMhnes')))],
            'payPerMonth' => ['required', 'decimal:0,2', 'between:0,99999999.99'],
            'payTotal' => ['required', 'decimal:0,2', 'between:0,99999999.99'],
            'wresPerDays' => ['required', 'integer', 'between:1,8'],
            'wresPerWeek' => ['required', 'integer', 'between:1,40'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date'],
        ];

        if ($this->fundingCode() === 'Espa') {
            $rules['wresPerWeek'] = ['required', 'integer', Rule::in([20, 40])];
            $rules['payPerMonth'] = ['exclude'];
            $rules['payTotal'] = ['exclude'];
        }

        if ($this->isDraft()) {
            $rules = collect($rules)->map(fn (array $fieldRules): array => collect($fieldRules)
                ->map(fn (mixed $rule): mixed => $rule === 'required' ? 'nullable' : $rule)
                ->all()
            )->all();
        }

        $configuredFields = app(FormFields::class)->student($this->fundingCode());
        $activeFields = StudentCardField::query()
            ->forCardSection('student')
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
            'afm.regex' => 'Το ΑΦΜ πρέπει να έχει 7 ή 9 ψηφία.',
            'amka.regex' => 'Το ΑΜΚΑ πρέπει να έχει ακριβώς 11 ψηφία.',
            'ama.regex' => 'Ο ΑΜΑ πρέπει να έχει 7 ή 9 ψηφία.',
            'iban.regex' => 'Το IBAN πρέπει να αρχίζει από GR και να ακολουθούν 25 ψηφία.',
            'address_tk.regex' => 'Ο ταχυδρομικός κώδικας πρέπει να έχει 5 ψηφία.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect(config('praktiki_fields.uppercase_fields'))
            ->filter(fn (string $field): bool => $this->has($field) && is_string($this->input($field)))
            ->mapWithKeys(fn (string $field): array => [$field => mb_strtoupper($this->input($field))])
            ->all());

        if ($this->filled('numMhnes') && is_numeric($this->input('numMhnes'))) {
            $this->merge(['numMhnes' => (string) (float) $this->input('numMhnes')]);
        }
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

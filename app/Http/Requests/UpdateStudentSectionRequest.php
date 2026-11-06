<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\StudentCardField;
use App\Support\RescomReferenceData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateStudentSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student && Gate::allows('update', $student);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return collect($this->editableFields())
            ->mapWithKeys(fn (string $field): array => [$field => $this->rulesFor($field)])
            ->all();
    }

    /** @return array<int, string> */
    public function editableFields(): array
    {
        $section = $this->route('section');

        if ($section === 'student') {
            return array_values(array_diff(
                $this->activeFields('student'),
                ['praxh_year', 'questionnaire_version'],
            ));
        }

        if ($section === 'provider') {
            return $this->activeFields('provider');
        }

        return $this->activeFields('office');
    }

    /** @return array<int, string> */
    private function rulesFor(string $field): array
    {
        $type = config('praktiki_fields.field_types.'.$field, 'text');

        if ($this->route('section') === 'office') {
            return [
                'sometimes',
                'nullable',
                Rule::in(config('praktiki_fields.office_status_values.'.$field, [0, 1])),
            ];
        }

        $formatRules = [
            'afm' => 'regex:/^([0-9]{7}|[0-9]{9})$/',
            'f_afm' => 'regex:/^([0-9]{7}|[0-9]{9})$/',
            'amka' => 'regex:/^[0-9]{11}$/',
            'AMF' => 'regex:/^[0-9]{13}$/',
            'address_tk' => 'regex:/^[0-9]{5}$/',
            'f_tk' => 'regex:/^[0-9]{5}$/',
            'f_ektPrakt_tk' => 'regex:/^[0-9]{5}$/',
            'ama' => 'regex:/^([0-9]{7}|[0-9]{9})$/',
            'tel' => 'regex:/^69[0-9]{8}$/',
            'iban' => 'regex:/^GR[0-9A-Z]{25}$/',
        ];

        if (isset($formatRules[$field])) {
            $rules = ['nullable', 'string'];

            if ((string) $this->input($field) !== (string) $this->currentValue($field)) {
                $rules[] = $formatRules[$field];
            }

            return $rules;
        }

        if (in_array($type, ['select', 'city'], true)) {
            $catalogName = config('praktiki_fields.catalog_fields.'.$field);
            $options = $catalogName
                ? app(RescomReferenceData::class)->options($catalogName)
                : config('praktiki_fields.select_options.'.$field, []);
            $allowedValues = array_keys($options);
            $currentValue = $this->currentValue($field);

            if ($currentValue !== null && $currentValue !== '') {
                $allowedValues[] = $currentValue;
            }

            $presenceRules = $field === 'diakoptomeno'
                ? ['sometimes', 'required']
                : ['nullable'];

            return [...$presenceRules, Rule::in(array_unique($allowedValues, SORT_REGULAR))];
        }

        if (in_array($field, ['startDate', 'endDate', 'endDate_DKPT_A', 'startDate_DKPT_B', 'adt_ek_date', 'dob'], true)) {
            return ['nullable', 'date'];
        }

        if (in_array($field, ['diakoptomeno', 'id_group', 'fylo_rescom', 'katEkp_rescom', 'doy_rescom', 'xwra_rescom', 'address_polh_rescom', 'doseis_count', 'merikhsPlhrhs', 'f_nomikhMorfh_rescom', 'f_doy_rescom', 'dhm_idiwt_rescom', 'f_proeleush_rescom', 'f_polh_rescom', 'f_xwra_rescom', 'f_ektPrakt_polh_rescom', 'f_perifereia'], true)) {
            return ['nullable', 'integer', 'min:'.config('praktiki_fields.field_minimums.'.$field, 0)];
        }

        if ($field === 'numChild') {
            return ['nullable', 'integer', 'between:0,255'];
        }

        if ($field === 'wresPerDays') {
            return ['nullable', 'integer', 'between:1,8'];
        }

        if ($field === 'wresPerWeek') {
            return ['nullable', 'integer', 'between:1,40'];
        }

        if ($field === 'payTotal') {
            return ['nullable', 'decimal:0,2', 'between:0,99999999.99'];
        }

        if ($field === 'payPerMonth') {
            return ['nullable', 'decimal:0,2', 'between:0,99999999.99'];
        }

        if ($field === 'numMhnes') {
            $allowedValues = array_keys(config('praktiki_fields.select_options.numMhnes'));
            $currentValue = $this->currentValue($field);

            if ($currentValue !== null && $currentValue !== '') {
                $allowedValues[] = $currentValue;
            }

            return ['nullable', Rule::in(array_unique($allowedValues, SORT_REGULAR))];
        }

        if ($type === 'email') {
            return ['nullable', 'email:rfc', 'max:'.($field === 'f_email' ? 100 : 50)];
        }

        $maxLength = match (true) {
            in_array($field, ['AMF', 'afm', 'amka', 'praxh_year', 'kwdForea'], true) => 20,
            in_array($field, ['f_tk', 'f_ektPrakt_tk'], true) => 10,
            in_array($field, ['email', 'tel', 'ama', 'iban', 'adt', 'f_epopt_email', 'f_thl'], true) => 50,
            $field === 'address_tk' => 5,
            in_array($field, ['f_epwnymia'], true) => 150,
            in_array($field, ['workDaysDescription', 'f_ekprFname', 'f_ekprLname', 'f_topos_ekt_prakt', 'f_ektPrakt_odos', 'f_epopt_foreaFname', 'f_epopt_foreaLname'], true) => 200,
            in_array($field, ['notes', 'f_epopt_foreaThesh', 'f_epopt_foreaKatartish', 'f_antikeim_Praktikhs', 'f_apotel_Praktikhs'], true) => 65535,
            default => 100,
        };

        return ['nullable', 'string', 'max:'.$maxLength];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect(config('praktiki_fields.uppercase_fields'))
            ->filter(fn (string $field): bool => $this->has($field) && is_string($this->input($field)))
            ->mapWithKeys(fn (string $field): array => [$field => mb_strtoupper(trim($this->input($field)))])
            ->all());

        if ($this->filled('numMhnes') && is_numeric($this->input('numMhnes'))) {
            $this->merge(['numMhnes' => (string) (float) $this->input('numMhnes')]);
        }
    }

    private function fundingCode(): string
    {
        /** @var Student $student */
        $student = $this->route('student');
        $student->loadMissing('phase.praktiki.funding');

        return $student->fundingCode();
    }

    private function currentValue(string $field): mixed
    {
        /** @var Student $student */
        $student = $this->route('student');

        return match ($this->route('section')) {
            'student' => $student->{$field},
            'provider' => $student->loadMissing('provider')->provider?->{$field},
            'office' => $student->action($field)?->status,
        };
    }

    /**
     * @return array<int, string>
     */
    private function activeFields(string $cardSection): array
    {
        return StudentCardField::query()
            ->forCardSection($cardSection)
            ->active()
            ->visibleFor($this->fundingCode(), 'member')
            ->orderBy('sort_order')
            ->pluck('field')
            ->all();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'afm.regex' => 'Το ΑΦΜ πρέπει να έχει 7 ή 9 ψηφία.',
            'f_afm.regex' => 'Το ΑΦΜ του φορέα πρέπει να έχει 7 ή 9 ψηφία.',
            'amka.regex' => 'Το ΑΜΚΑ πρέπει να έχει ακριβώς 11 ψηφία.',
            'AMF.regex' => 'Ο αριθμός μητρώου φοιτητή πρέπει να έχει ακριβώς 13 ψηφία.',
            'address_tk.regex' => 'Ο ταχυδρομικός κώδικας πρέπει να έχει 5 ψηφία.',
            'f_tk.regex' => 'Ο ταχυδρομικός κώδικας του φορέα πρέπει να έχει 5 ψηφία.',
            'f_ektPrakt_tk.regex' => 'Ο ταχυδρομικός κώδικας του τόπου εκτέλεσης πρέπει να έχει 5 ψηφία.',
            'ama.regex' => 'Ο ΑΜΑ πρέπει να έχει 7 ή 9 ψηφία.',
            'tel.regex' => 'Το κινητό πρέπει να έχει 10 ψηφία και να ξεκινά από 69.',
            'iban.regex' => 'Το IBAN πρέπει να είναι ελληνικό, να ξεκινά από GR και να έχει 27 χαρακτήρες.',
            'min' => 'Η τιμή δεν μπορεί να είναι αρνητική.',
            'in' => 'Η επιλεγμένη τιμή δεν είναι έγκυρη.',
        ];
    }
}

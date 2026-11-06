<?php

namespace App\Http\Requests;

use App\Models\Funding;
use App\Models\Praktiki;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SavePraktikiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $praktiki = $this->route('praktiki');

        return $praktiki instanceof Praktiki
            ? Gate::allows('update', $praktiki)
            : Gate::allows('create', Praktiki::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $funding = $this->fundingCode();
        $fundingId = Funding::query()->where('active', true)->where('code', $funding)->value('id');
        $praktiki = $this->route('praktiki');
        $rules = [
            'funding' => ['required', Rule::in(array_keys(config('praktiki_internship.cycle_fields'))), Rule::exists('fundings', 'code')->where('active', true)],
            'praxh_year' => [
                'required', 'string', 'max:20',
                Rule::unique('praktiki_cycles')->where(fn ($query) => $query->where('funding_id', $fundingId))->ignore($praktiki?->id),
            ],
            'notes' => ['nullable', 'string', 'max:10000'],
            'active' => ['required', 'boolean'],
        ];

        foreach (config("praktiki_internship.cycle_fields.{$funding}", []) as $field => $definition) {
            $rules[$field] = ['nullable', 'string', 'max:'.$definition['max']];
        }

        return $rules;
    }

    public function fundingCode(): string
    {
        $praktiki = $this->route('praktiki');

        return $praktiki instanceof Praktiki ? $praktiki->loadMissing('funding')->funding_code : $this->string('funding')->toString();
    }

    /** @return array<string, mixed> */
    public function detailValues(): array
    {
        $validated = $this->validated();

        return collect(array_keys(config('praktiki_internship.cycle_fields.'.$this->fundingCode(), [])))
            ->mapWithKeys(fn (string $field): array => [$field => $validated[$field] ?? null])
            ->all();
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Fasi;
use App\Models\Praktiki;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveFasiRequest extends FormRequest
{
    public const PHASE_NAMES = ['Α', 'Β', 'Γ', 'Δ', 'Ε', 'ΣΤ', 'Α1', 'Α2', 'Β1', 'Β2'];

    public function authorize(): bool
    {
        $fasi = $this->route('fasi');

        return $fasi instanceof Fasi
            ? Gate::allows('update', $fasi)
            : ! $this->user()->view_only;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $fasi = $this->route('fasi');
        $praktiki = $this->praktiki();
        $departmentIds = $this->user()->role === 'admin'
            ? null
            : $this->user()->manageableDepartments($praktiki->funding_code)->pluck('departments.id')->all();
        $departmentId = $this->integer('department_id');
        $rules = [
            'praktiki_cycle_id' => ['required', 'integer', Rule::exists('praktiki_cycles', 'id')],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'name' => [
                'required', Rule::in(self::PHASE_NAMES),
                Rule::unique('praktiki_phases', 'name')->where(fn ($query) => $query
                    ->where('department_id', $departmentId)
                    ->where('praktiki_cycle_id', $praktiki->id))->ignore($fasi?->id),
            ],
            'dates' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];

        if ($departmentIds !== null) {
            $rules['department_id'][] = Rule::in($departmentIds);
        }

        foreach (array_keys(config('praktiki_internship.phase_urls')) as $field) {
            $rules[$field] = ['nullable', 'string', 'max:500'];
        }
        foreach (config('praktiki_internship.phase_fields.'.$praktiki->funding_code, []) as $field => $definition) {
            $rules[$field] = ['nullable', 'string', 'max:'.$definition['max']];
        }

        return $rules;
    }

    public function praktiki(): Praktiki
    {
        $fasi = $this->route('fasi');

        return $fasi instanceof Fasi
            ? $fasi->loadMissing('praktiki.funding')->praktiki
            : Praktiki::query()->with('funding')->findOrFail($this->integer('praktiki_cycle_id'));
    }

    /** @return array<string, mixed> */
    public function commonValues(): array
    {
        $validated = $this->validated();
        $values = [
            'name' => $validated['name'],
            'dates' => $validated['dates'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        foreach (array_keys(config('praktiki_internship.phase_urls')) as $field) {
            $values[$field] = $validated[$field] ?? null;
        }

        return $values;
    }

    /** @return array<string, mixed> */
    public function detailValues(): array
    {
        $validated = $this->validated();

        return collect(array_keys(config('praktiki_internship.phase_fields.'.$this->praktiki()->funding_code, [])))
            ->mapWithKeys(fn (string $field): array => [$field => $validated[$field] ?? null])
            ->all();
    }
}

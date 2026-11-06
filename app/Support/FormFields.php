<?php

namespace App\Support;

class FormFields
{
    /** @return list<string> */
    public function student(string $funding): array
    {
        return $this->editableForFunding('student', $funding);
    }

    /** @return list<string> */
    public function provider(string $funding): array
    {
        return $this->editableForFunding('provider', $funding);
    }

    /** @return list<string> */
    public function studentReadOnly(): array
    {
        return config('praktiki_fields.student_readonly_fields', []);
    }

    /** @return list<string> */
    private function editableForFunding(string $section, string $funding): array
    {
        return array_values(array_unique([
            ...config("praktiki_fields.{$section}_editable_common_fields", []),
            ...config("praktiki_fields.{$section}_editable_funding_fields.{$funding}", []),
        ]));
    }
}

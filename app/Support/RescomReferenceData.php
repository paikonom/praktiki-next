<?php

namespace App\Support;

use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class RescomReferenceData
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $loadedDataSets = [];

    /** @return array<string, string> */
    public function options(string $dataSet): array
    {
        return collect($this->rows($dataSet))
            ->mapWithKeys(fn (array $row): array => [(string) $row['Κωδικός'] => $this->description($dataSet, $row)])
            ->all();
    }

    public function label(string $dataSet, mixed $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return $this->options($dataSet)[(string) $code] ?? null;
    }

    /** @return array<int, array{id: string, text: string}> */
    public function searchCities(string $search, int $limit = 20): array
    {
        $needle = $this->normalize($search);

        if (mb_strlen($needle) < 2) {
            return [];
        }

        $matches = [];

        foreach ($this->rows('cities') as $row) {
            $text = $this->description('cities', $row);

            if (! str_contains($this->normalize($text), $needle)) {
                continue;
            }

            $matches[] = ['id' => (string) $row['Κωδικός'], 'text' => $text];

            if (count($matches) === $limit) {
                break;
            }
        }

        return $matches;
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(string $dataSet): array
    {
        if (! in_array($dataSet, ['cities', 'countries', 'doys'], true)) {
            throw new RuntimeException("Unknown ResCom reference data set: {$dataSet}");
        }

        if (isset($this->loadedDataSets[$dataSet])) {
            return $this->loadedDataSets[$dataSet];
        }

        $path = resource_path("data/{$dataSet}.json");

        if (! is_file($path)) {
            throw new RuntimeException("Missing ResCom reference data set: {$dataSet}");
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid ResCom reference data set: {$dataSet}", previous: $exception);
        }

        return $this->loadedDataSets[$dataSet] = $data['Table'] ?? throw new RuntimeException("Missing Table in ResCom reference data set: {$dataSet}");
    }

    /** @param array<string, mixed> $row */
    private function description(string $dataSet, array $row): string
    {
        if ($dataSet === 'cities') {
            return trim(($row['Νομός'] ?? '').' - '.$row['Περιγραφή'], ' -');
        }

        return (string) $row['Περιγραφή'];
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(trim($value)));
    }
}

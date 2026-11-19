<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class StudentDocumentMergeService
{
    private const DOCUMENT_CODES = [
        'vevaiwsh_EY',
        'vevaiwsh_Forea',
        'symf_SN_FULL',
        'ADA_diaugeia',
        'erganh_enarxh',
        'erganh_lhxh',
        'vathm_foit',
        'hmer_programma',
        'ekthesh_Forea',
        'ekthesh_Foit',
        'input',
        'output',
        'f_atlas',
        'extra_docs',
    ];

    /** @return Collection<int, array{title: string, document: StudentDocument|null, pages: int|null, reason: string|null}> */
    public function inspect(Student $student): Collection
    {
        $types = DocumentType::visibleForStudentAndRole($student, 'member')
            ->whereIn('code', self::DOCUMENT_CODES)
            ->keyBy('code');

        return collect(self::DOCUMENT_CODES)->flatMap(function (string $code) use ($types): array {
            $type = $types->get($code);

            if (! $type) {
                return [];
            }

            $documents = $type->documents->where('status', 'approved');

            if ($code !== 'extra_docs') {
                $documents = collect([$documents->first()]);
            } else {
                $documents = $documents->sortBy('version');
            }

            return $documents->map(function (?StudentDocument $document) use ($type): array {

                if (! $document || blank($document->path)) {
                    return $this->result($type->title, $document, 'Δεν υπάρχει εγκεκριμένο αρχείο.');
                }

                if (! Storage::disk($document->disk)->exists($document->path)) {
                    return $this->result($type->title, $document, 'Το αρχείο λείπει από τον δίσκο.');
                }

                try {
                    $process = new Process([
                        'qpdf', '--warning-exit-0', '--show-npages',
                        Storage::disk($document->disk)->path($document->path),
                    ]);
                    $process->setTimeout(30)->mustRun();

                    return $this->result($type->title, $document, null, (int) trim($process->getOutput()));
                } catch (Throwable) {
                    return $this->result($type->title, $document, 'Το PDF δεν μπορεί να διαβαστεί.');
                }
            })->all();
        })->values();
    }

    public function merge(Student $student): string
    {
        $documents = $this->inspect($student)->whereNull('reason');

        if ($documents->isEmpty()) {
            throw new RuntimeException('Δεν υπάρχει εγκεκριμένο PDF που μπορεί να συγχωνευτεί.');
        }

        $path = tempnam(sys_get_temp_dir(), 'praktiki-merged-');

        if ($path === false) {
            throw new RuntimeException('Δεν δημιουργήθηκε το προσωρινό αρχείο PDF.');
        }

        $inputPaths = $documents->map(function (array $item): string {
            $document = $item['document'];

            return Storage::disk($document->disk)->path($document->path);
        })->all();

        try {
            $process = new Process(['qpdf', '--empty', '--pages', ...$inputPaths, '--', $path]);
            $process->setTimeout(120)->mustRun();
        } catch (Throwable $exception) {
            @unlink($path);

            throw new RuntimeException('Δεν ολοκληρώθηκε η συγχώνευση των PDF.', previous: $exception);
        }

        return $path;
    }

    /** @return array{title: string, document: StudentDocument|null, pages: int|null, reason: string|null} */
    private function result(string $title, ?StudentDocument $document, ?string $reason, ?int $pages = null): array
    {
        return compact('title', 'document', 'pages', 'reason');
    }
}

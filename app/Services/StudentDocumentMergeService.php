<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;
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
                    $pages = $this->pdf()->setSourceFile(Storage::disk($document->disk)->path($document->path));

                    return $this->result($type->title, $document, null, $pages);
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

        $pdf = $this->pdf();

        foreach ($documents as $item) {
            $document = $item['document'];
            $path = Storage::disk($document->disk)->path($document->path);
            $pageCount = $pdf->setSourceFile($path);

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'praktiki-merged-');

        if ($path === false) {
            throw new RuntimeException('Δεν δημιουργήθηκε το προσωρινό αρχείο PDF.');
        }

        $pdf->Output('F', $path);

        return $path;
    }

    /** @return array{title: string, document: StudentDocument|null, pages: int|null, reason: string|null} */
    private function result(string $title, ?StudentDocument $document, ?string $reason, ?int $pages = null): array
    {
        return compact('title', 'document', 'pages', 'reason');
    }

    private function pdf(): Fpdi
    {
        $commercialParser = base_path('fpdi-pdf-parser/src/autoload.php');

        if (is_file($commercialParser)) {
            require_once $commercialParser;
        }

        return new Fpdi;
    }
}

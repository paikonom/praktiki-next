<?php

namespace App\Console\Commands;

use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentDocument;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

#[Signature('documents:import {source : Absolute path containing the student upload folders} {--write : Create records and copy files; without this option only report}')]
#[Description('Import approved student documents from the existing upload folders')]
class ImportStudentDocuments extends Command
{
    /** @var array<string, int> */
    private array $stats = [
        'pdf_files' => 0,
        'ready' => 0,
        'imported' => 0,
        'existing' => 0,
        'department_files' => 0,
        'unknown_type' => 0,
        'missing_student' => 0,
        'missing_handler' => 0,
        'funding_mismatch' => 0,
        'invalid_name' => 0,
        'copy_failed' => 0,
    ];

    /** @var array<string, int> */
    private array $issues = [];

    public function handle(): int
    {
        $source = rtrim((string) $this->argument('source'), DIRECTORY_SEPARATOR);

        if (! is_dir($source) || ! is_readable($source)) {
            $this->error("Source directory is not readable: {$source}");

            return self::FAILURE;
        }

        $students = Student::query()
            ->with('phase.praktiki.funding')
            ->get()
            ->keyBy('id');
        $types = DocumentType::query()
            ->with(['fundings', 'roles'])
            ->get()
            ->keyBy('code');
        $handlers = $this->handlerMap();
        $existing = StudentDocument::query()
            ->get(['s_id', 'document_type_id'])
            ->mapWithKeys(fn (StudentDocument $document) => ["{$document->s_id}:{$document->document_type_id}" => true]);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relativePath = ltrim(substr($file->getPathname(), strlen($source)), DIRECTORY_SEPARATOR);

            if (str_starts_with($relativePath, 'tmhmata'.DIRECTORY_SEPARATOR)) {
                if (strtolower($file->getExtension()) === 'pdf') {
                    $this->stats['department_files']++;
                }

                continue;
            }

            if (strtolower($file->getExtension()) !== 'pdf') {
                continue;
            }

            $this->stats['pdf_files']++;
            $candidate = $this->candidate($file, $students, $types, $handlers, $existing);

            if ($candidate === null) {
                continue;
            }

            $this->stats['ready']++;

            if (! $this->option('write')) {
                continue;
            }

            try {
                $this->import($candidate);
                $existing->put("{$candidate['student']->id}:{$candidate['type']->id}", true);
                $this->stats['imported']++;
            } catch (Throwable $exception) {
                $this->stats['copy_failed']++;
                $this->issue('copy_failed', $relativePath.' | '.$exception->getMessage());
            }
        }

        $this->report();

        $errorCount = $this->stats['unknown_type']
            + $this->stats['missing_student']
            + $this->stats['missing_handler']
            + $this->stats['funding_mismatch']
            + $this->stats['invalid_name']
            + $this->stats['copy_failed'];

        if (! $this->option('write')) {
            $this->info('Dry run only. Run again with --write to create records and copy the ready files.');
        }

        return $errorCount === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  Collection<string, DocumentType>  $types
     * @param  array<int, array<string, int>>  $handlers
     * @param  Collection<string, bool>  $existing
     * @return array{source: string, student: Student, type: DocumentType, handler_id: int, upload_role: string}|null
     */
    private function candidate(
        SplFileInfo $file,
        Collection $students,
        Collection $types,
        array $handlers,
        Collection $existing,
    ): ?array {
        $folder = basename($file->getPath());

        if (! ctype_digit($folder)) {
            $this->stats['invalid_name']++;
            $this->issue('invalid_name', $file->getPathname());

            return null;
        }

        $studentId = (int) $folder;
        $student = $students->get($studentId);

        if (! $student) {
            $this->stats['missing_student']++;
            $this->issue('missing_student', (string) $studentId);

            return null;
        }

        $baseName = $file->getBasename('.'.$file->getExtension());
        $prefix = $folder.'_';
        $code = str_starts_with($baseName, $prefix) ? substr($baseName, strlen($prefix)) : $baseName;
        $type = $types->get($code);

        if (! $type) {
            $this->stats['unknown_type']++;
            $this->issue('unknown_type', "{$studentId}:{$code}");

            return null;
        }

        $key = "{$studentId}:{$type->id}";
        if ($existing->has($key)) {
            $this->stats['existing']++;

            return null;
        }

        $funding = $student->fundingCode();
        if (! $type->fundings->contains('funding_code', $funding)) {
            $this->stats['funding_mismatch']++;
            $this->issue('funding_mismatch', "{$studentId}:{$code}:{$funding}");

            return null;
        }

        $handlerId = $handlers[$student->department_id][$funding] ?? null;
        if (! $handlerId) {
            $this->stats['missing_handler']++;
            $this->issue('missing_handler', "department={$student->department_id}, funding={$funding}");

            return null;
        }

        $uploadRole = match (true) {
            $type->roles->contains(fn ($role) => $role->role === 'student' && $role->can_upload) => 'student',
            $type->roles->contains(fn ($role) => $role->role === 'provider' && $role->can_upload) => 'provider',
            default => 'member',
        };

        return [
            'source' => $file->getPathname(),
            'student' => $student,
            'type' => $type,
            'handler_id' => $handlerId,
            'upload_role' => $uploadRole,
        ];
    }

    /** @param array{source: string, student: Student, type: DocumentType, handler_id: int, upload_role: string} $candidate */
    private function import(array $candidate): void
    {
        $storedPath = null;

        try {
            DB::transaction(function () use ($candidate, &$storedPath): void {
                $student = $candidate['student'];
                $type = $candidate['type'];
                $timestamp = Carbon::createFromTimestamp(filemtime($candidate['source']) ?: time());
                $uploadRole = $candidate['upload_role'];
                $document = StudentDocument::create([
                    's_id' => $student->id,
                    'document_type_id' => $type->id,
                    'version' => 1,
                    'disk' => 'local',
                    'path' => 'pending',
                    'original_name' => basename($candidate['source']),
                    'mime_type' => 'application/pdf',
                    'size' => filesize($candidate['source']),
                    'status' => 'approved',
                    'uploaded_by_role' => $uploadRole,
                    'uploaded_by_id' => $uploadRole === 'member' ? $candidate['handler_id'] : $student->id,
                    'uploaded_at' => $timestamp,
                    'reviewed_by' => $candidate['handler_id'],
                    'reviewed_at' => $timestamp,
                ]);
                $storedPath = "students/{$student->id}/documents/{$document->id}.pdf";
                $stream = fopen($candidate['source'], 'rb');

                if (! is_resource($stream)) {
                    throw new RuntimeException('The source file could not be opened.');
                }

                try {
                    if (! Storage::disk('local')->put($storedPath, $stream)) {
                        throw new RuntimeException('The destination file could not be written.');
                    }
                } finally {
                    fclose($stream);
                }

                $document->update(['path' => $storedPath]);
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    /** @return array<int, array<string, int>> */
    private function handlerMap(): array
    {
        $map = [];

        foreach (DB::table('user_department_access')->get() as $access) {
            foreach ([
                'Espa' => 'can_manage_espa',
                'Institution' => 'can_manage_institution',
                'InternshipProvider' => 'can_manage_internship_provider',
            ] as $funding => $column) {
                if ((int) $access->{$column} !== 1) {
                    continue;
                }

                if (isset($map[$access->department_id][$funding])) {
                    throw new RuntimeException("Multiple handlers for department {$access->department_id} and {$funding}.");
                }

                $map[$access->department_id][$funding] = (int) $access->user_id;
            }
        }

        return $map;
    }

    private function issue(string $reason, string $value): void
    {
        $key = "{$reason} | {$value}";
        $this->issues[$key] = ($this->issues[$key] ?? 0) + 1;
    }

    private function report(): void
    {
        $this->table(['Result', 'Count'], collect($this->stats)->map(fn ($count, $name) => [$name, $count])->values());

        if ($this->issues !== []) {
            arsort($this->issues);
            $this->newLine();
            $this->warn('Issues (grouped):');
            $this->table(
                ['Issue', 'Count'],
                collect($this->issues)->take(50)->map(fn ($count, $issue) => [$issue, $count])->values(),
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOwnProviderRequest;
use App\Http\Requests\UpdateOwnStudentRequest;
use App\Models\Student;
use App\Models\StudentAction;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Notifications\StudentActionNotification;
use App\Support\FormFields;
use App\Support\RescomReferenceData;
use App\Support\StudentCardMetadata;
use App\Support\VisibleInternshipDetails;
use App\Support\WorkflowDisplay;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPortalController extends Controller
{
    public function __construct(
        private StudentCardMetadata $metadata,
        private FormFields $formFields,
    ) {}

    public function editStudentDetails(Request $request, RescomReferenceData $rescomReferenceData): View
    {
        $student = $this->student($request);

        if ($this->fundingCode($student) === 'Espa') {
            $student->payPerMonth = match ((int) $student->wresPerWeek) {
                20 => 175,
                40 => 350,
                default => $student->payPerMonth,
            };
            $student->payTotal = round((float) $student->numMhnes * (float) $student->payPerMonth, 2);
        }

        return view('student.details-form', $this->formData(
            $request,
            $rescomReferenceData,
            $student,
            $student,
            $this->profileFields($student),
            $this->profileReadOnlyFields($student),
            'student.details.student.update',
            'student',
            'Στοιχεία φοιτητή',
            'Ελέγξτε προσεκτικά τα στοιχεία. Μετά την οριστική υποβολή δεν μπορούν να αλλάξουν από εδώ.',
            ! $student->actionHasStatus('stoix_student', [StudentAction::STATUS_SUBMITTED, StudentAction::STATUS_APPROVED]),
        ));
    }

    public function updateStudentDetails(UpdateOwnStudentRequest $request): RedirectResponse
    {
        $student = $this->student($request);
        $data = $request->validated();
        $funding = $this->fundingCode($student);
        $isDraft = $request->isDraft();
        $newStatus = $isDraft ? StudentAction::STATUS_DRAFT : StudentAction::STATUS_SUBMITTED;
        abort_unless(WorkflowAction::statusIsAllowedFor($student, 'action', 'stoix_student', $newStatus), 422, 'Η κατάσταση δεν επιτρέπεται για τα στοιχεία φοιτητή.');

        DB::transaction(function () use ($student, $data, $funding, $isDraft, $newStatus): void {
            if ($funding === 'Espa') {
                $weeklyHoursSubmitted = array_key_exists('wresPerWeek', $data);
                $monthsSubmitted = array_key_exists('numMhnes', $data);

                if ($weeklyHoursSubmitted || $monthsSubmitted) {
                    $weeklyHours = (int) ($weeklyHoursSubmitted ? $data['wresPerWeek'] : $student->wresPerWeek);
                    $months = $monthsSubmitted ? $data['numMhnes'] : $student->numMhnes;
                    $data['payPerMonth'] = match ($weeklyHours) {
                        20 => 175,
                        40 => 350,
                        default => null,
                    };
                    $data['payTotal'] = $months !== null && $data['payPerMonth'] !== null
                        ? round((float) $months * $data['payPerMonth'], 2)
                        : null;
                }
            }

            $student->forceFill($data)->save();

            $student->actions()->updateOrCreate(['action' => 'stoix_student'], [
                'status' => $newStatus,
                'submitted_at' => $isDraft ? null : now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_comment' => null,
            ]);
        });

        if ($isDraft) {
            return redirect()->route('student.details.student.edit')->with('status', 'Η προσωρινή αποθήκευση ολοκληρώθηκε. Μπορείτε να συνεχίσετε αργότερα.');
        }

        $this->notifyMembers(
            $student,
            'Οριστική υποβολή στοιχείων φοιτητή',
            'Τα στοιχεία φοιτητή υποβλήθηκαν οριστικά.',
            'student',
        );

        return redirect()->route('student.dashboard')->with('status', 'Τα στοιχεία φοιτητή υποβλήθηκαν οριστικά.');
    }

    public function editProviderDetails(Request $request, RescomReferenceData $rescomReferenceData): View
    {
        $student = $this->student($request);
        $provider = $student->provider()->firstOrNew();

        return view('student.details-form', $this->formData(
            $request,
            $rescomReferenceData,
            $student,
            $provider,
            $this->providerFields($student),
            [],
            'student.details.provider.update',
            'provider',
            'Στοιχεία φορέα υποδοχής',
            'Συμπληρώστε τα στοιχεία του φορέα και του επόπτη στον φορέα.',
            ! $student->actionHasStatus('stoix_forea', [StudentAction::STATUS_SUBMITTED, StudentAction::STATUS_APPROVED]),
        ));
    }

    public function updateProviderDetails(UpdateOwnProviderRequest $request): RedirectResponse
    {
        $student = $this->student($request);
        $isDraft = $request->isDraft();
        $newStatus = $isDraft ? StudentAction::STATUS_DRAFT : StudentAction::STATUS_SUBMITTED;
        abort_unless(WorkflowAction::statusIsAllowedFor($student, 'action', 'stoix_forea', $newStatus), 422, 'Η κατάσταση δεν επιτρέπεται για τα στοιχεία φορέα.');

        DB::transaction(function () use ($student, $request, $isDraft, $newStatus): void {
            $student->provider()->firstOrNew()->forceFill($request->validated())->save();

            $student->actions()->updateOrCreate(['action' => 'stoix_forea'], [
                'status' => $newStatus,
                'submitted_at' => $isDraft ? null : now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_comment' => null,
            ]);
        });

        if ($isDraft) {
            return redirect()->route('student.details.provider.edit')->with('status', 'Η προσωρινή αποθήκευση ολοκληρώθηκε. Μπορείτε να συνεχίσετε αργότερα.');
        }

        $this->notifyMembers(
            $student,
            'Οριστική υποβολή στοιχείων φορέα',
            'Τα στοιχεία φορέα υποβλήθηκαν οριστικά.',
            'provider',
        );

        return redirect()->route('student.dashboard')->with('status', 'Τα στοιχεία του φορέα υποβλήθηκαν οριστικά.');
    }

    public function details(
        Request $request,
        RescomReferenceData $rescomReferenceData,
        VisibleInternshipDetails $visibleInternshipDetails,
    ): View {
        $student = $this->student($request);

        $internshipDetailGroups = collect($visibleInternshipDetails->forRole(
            $student,
            $rescomReferenceData,
            'student',
        ))->whereIn('key', ['internship', 'phase', 'department'])->values();

        return view('shared.internship-details-page', [
            'student' => $student,
            'portalRole' => 'student',
            'activePortalPage' => 'details',
            'pageTitle' => 'Στοιχεία πρακτικής',
            'internshipDetailGroups' => $internshipDetailGroups,
        ]);
    }

    public function contact(Request $request): View
    {
        return view('student.contact', ['student' => $this->student($request)]);
    }

    public function records(Request $request, WorkflowDisplay $workflowDisplay): View
    {
        $student = $this->student($request);

        $recordRows = collect($workflowDisplay->sections($student, 'student'))
            ->flatMap(fn (array $section): array => $section['rows'])
            ->whereIn('sourceType', ['apografiko', 'student_questionnaire'])
            ->map(function (array $row): array {
                $pageUrl = $row['sourceType'] === 'apografiko'
                    ? route('student.apografika.show', $row['sourceKey'])
                    : route('student.questionnaire');
                $row['pdfUrl'] = match (true) {
                    $row['sourceType'] === 'apografiko' && $row['status'] === 'completed' => route('student.apografika.pdf', $row['sourceKey']),
                    $row['sourceType'] === 'student_questionnaire' && $row['status'] === 'submitted' => route('student.questionnaire.pdf'),
                    default => null,
                };
                [$row['recordUrl'], $row['recordLabel']] = match ($row['status']) {
                    null, 'pending' => [$pageUrl, 'Συμπλήρωση'],
                    'draft', 'rejected' => [$pageUrl, 'Συνέχεια συμπλήρωσης'],
                    'failed' => [$pageUrl, 'Νέα προσπάθεια'],
                    'processing' => [$pageUrl, 'Έλεγχος κατάστασης'],
                    default => [null, null],
                };

                return $row;
            })
            ->values();

        return view('student.records', [
            'student' => $student,
            'recordRows' => $recordRows,
        ]);
    }

    private function student(Request $request): Student
    {
        /** @var Student $student */
        $student = $request->user('student');

        return $student->loadMissing(['department', 'phase.praktiki.funding', 'provider', 'actions']);
    }

    private function fundingCode(Student $student): string
    {
        return $student->fundingCode();
    }

    private function notifyMembers(Student $student, string $title, string $message, string $panel): void
    {
        $recipients = collect([$student->handler(), User::find(1)])->filter()->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new StudentActionNotification(
                config('praktiki_workflow.role_labels.student').' - '.$title,
                $student->full_name.': '.$message,
                route('students.show', ['student' => $student->id, 'panel' => $panel]),
                'student',
                $student->id,
                'bi-person-vcard',
            ));
        }
    }

    private function profileFields(Student $student): array
    {
        $names = $this->formFields->student($this->fundingCode($student));

        return $this->metadata->labels($this->metadata->fields(
            'student', $this->fundingCode($student), 'student', $names,
        ));
    }

    private function profileReadOnlyFields(Student $student): array
    {
        return $this->metadata->labels($this->metadata->fields(
            'student', $this->fundingCode($student), 'student',
            $this->formFields->studentReadOnly(),
        ));
    }

    private function providerFields(Student $student): array
    {
        return $this->metadata->labels($this->metadata->fields(
            'provider', $this->fundingCode($student), 'student',
            $this->formFields->provider($this->fundingCode($student)),
        ));
    }

    private function formData(
        Request $request,
        RescomReferenceData $rescomReferenceData,
        Student $student,
        object $record,
        array $fields,
        array $readOnlyFields,
        string $route,
        string $context,
        string $title,
        string $description,
        bool $canEdit,
    ): array {
        $fieldOptions = config('praktiki_fields.select_options');

        if ($context === 'student' && $this->fundingCode($student) === 'Espa') {
            $fieldOptions['wresPerWeek'] = [20 => '20 - Μερική απασχόληση', 40 => '40 - Πλήρης απασχόληση'];
        }

        foreach (config('praktiki_fields.catalog_fields') as $field => $catalogName) {
            if ($catalogName !== 'cities') {
                $fieldOptions[$field] = $rescomReferenceData->options($catalogName);
            }
        }

        $cityLabels = collect(['address_polh_rescom', 'f_polh_rescom', 'f_ektPrakt_polh_rescom'])
            ->mapWithKeys(fn (string $field): array => [
                $field => $rescomReferenceData->label('cities', $request->old($field, $record->{$field} ?? null)) ?? '',
            ])->all();

        $metadata = $this->metadata->fields(
            $context, $this->fundingCode($student), 'student', array_keys($fields),
        );
        $fieldHelp = $this->metadata->help($metadata);

        if ($context === 'student' && $this->fundingCode($student) === 'Espa') {
            $fieldHelp['wresPerWeek'] = 'Επιλέξτε 20 ή 40 ώρες.';
            $fieldHelp['payPerMonth'] = 'Υπολογίζεται από τις εβδομαδιαίες ώρες.';
            $fieldHelp['payTotal'] = 'Υπολογίζεται από τη διάρκεια και τη μηνιαία αποζημίωση.';
        }
        $fieldGroups = $this->metadata->groups($metadata);

        return compact('student', 'record', 'fields', 'readOnlyFields', 'fieldOptions', 'cityLabels', 'fieldHelp', 'fieldGroups', 'route', 'context', 'title', 'description', 'canEdit');
    }
}

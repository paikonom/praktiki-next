@extends('layouts.app')

@section('title', $student->full_name)

@section('content')
    @include('students.partials.summary', ['backUrl' => route('scientific_supervisor.dashboard'), 'showNotes' => false])

    @php
        $internshipDetails = collect($visibleInternshipDetails)->whereIn('key', ['internship', 'phase', 'department'])->values();
        $studentDetails = collect($visibleInternshipDetails)->where('key', 'student')->values();
        $providerDetails = collect($visibleInternshipDetails)->where('key', 'provider')->values();
    @endphp

    <div class="row g-4 align-items-start">
        <aside class="col-lg-3 col-xl-2 sticky-lg-top">
            <div class="list-group shadow-sm" role="tablist">
                <button class="list-group-item list-group-item-action {{ $panel === 'actions' ? 'active' : '' }}" data-bs-toggle="list" data-bs-target="#actions" type="button" role="tab">Ενέργειες</button>
                <button class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#internship" type="button" role="tab">Στοιχεία πρακτικής</button>
                <button class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#student-details" type="button" role="tab">Στοιχεία φοιτητή</button>
                <button class="list-group-item list-group-item-action" data-bs-toggle="list" data-bs-target="#provider-details" type="button" role="tab">Στοιχεία φορέα</button>
                <button class="list-group-item list-group-item-action {{ $panel === 'documents' ? 'active' : '' }}" data-bs-toggle="list" data-bs-target="#documents" type="button" role="tab">Έντυπα και ιστορικό</button>
                <!-- <span class="list-group-item text-muted">Αλλαγή κωδικού — σύντομα</span> -->
            </div>
        </aside>

        <div class="col-lg-9 col-xl-10 tab-content">
            <section id="actions" class="tab-pane fade {{ $panel === 'actions' ? 'show active' : '' }}" role="tabpanel">
                @include('shared.workflow', ['sections' => $workflowSections, 'currentRole' => 'scientific_supervisor', 'accordionId' => 'supervisor-workflow'])
            </section>
            <section id="internship" class="tab-pane fade" role="tabpanel">
                @include('shared.internship-details', ['internshipDetailGroups' => $internshipDetails, 'accordionId' => 'supervisor-internship-details'])
            </section>
            <section id="student-details" class="tab-pane fade" role="tabpanel">
                @include('shared.internship-details', ['internshipDetailGroups' => $studentDetails, 'accordionId' => 'supervisor-student-details', 'flat' => true])
            </section>
            <section id="provider-details" class="tab-pane fade" role="tabpanel">
                @include('shared.internship-details', ['internshipDetailGroups' => $providerDetails, 'accordionId' => 'supervisor-provider-details', 'flat' => true])
            </section>
            <section id="documents" class="tab-pane fade {{ $panel === 'documents' ? 'show active' : '' }}" role="tabpanel">
                @include('documents.index', ['types' => $documentTypes, 'role' => 'scientific_supervisor', 'embedded' => true])
            </section>
        </div>
    </div>
@endsection

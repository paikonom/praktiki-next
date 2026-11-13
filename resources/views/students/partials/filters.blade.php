<form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label for="year" class="form-label">Έτος πράξης</label>
                <select id="year" name="year" class="form-select">
                    <option value="all" @selected($year === 'all')>Όλα τα έτη</option>
                    @foreach ($years as $availableYear)
                        <option value="{{ $availableYear }}" @selected($availableYear === $year)>{{ $availableYear }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="funding" class="form-label">Χρηματοδότηση</label>
                <select id="funding" name="funding" class="form-select">
                    <option value="all" @selected($funding === 'all')>Όλες οι χρηματοδοτήσεις</option>
                    @foreach ($fundings as $availableFunding)
                        <option value="{{ $availableFunding->code }}" @selected($availableFunding->code === $funding)>
                            {{ $availableFunding->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="department_id" class="form-label">Τμήμα</label>
                <select id="department_id" name="department_id" class="form-select">
                    <option value="">Όλα τα τμήματα</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected($department->id === $departmentId)>
                            {{ $department->atlasId }} - {{ $department->tmhma }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="phase" class="form-label">Φάση</label>
                <select id="phase" name="phase" class="form-select">
                    <option value="">Όλες</option>
                    @foreach ($phaseNames as $availablePhase)
                        <option value="{{ $availablePhase }}" @selected($availablePhase === $phase)>{{ $availablePhase }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Προβολή</button>
            </div>
            <div class="col-md-10">
                <!-- <label for="search" class="form-label">Αναζήτηση</label> -->
                <input id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Ονοματεπώνυμο, ΑΜ, ΑΦΜ ή ΑΔΤ">
            </div>
            <div class="col-md-2">
                <a href="{{ route('students.index') }}" class="btn btn-outline-secondary w-100">Καθαρισμός</a>
            </div>
        </div>

        @php
            $filterLabels = [
                'student_status' => ['active' => 'Ενεργή πρακτική', 'completed' => 'Ολοκληρωμένη πρακτική', 'cancelled' => 'Ακυρωμένη πρακτική'],
                'date_stage' => ['before_start' => 'Πριν την έναρξη', 'in_progress' => 'Σε εξέλιξη', 'after_end' => 'Μετά τη λήξη'],
                'weekly_hours' => ['20' => '20 ώρες/εβδομάδα', '40' => '40 ώρες/εβδομάδα'],
                'atlas_position' => ['with' => 'Με θέση ΑΤΛΑΣ', 'without' => 'Χωρίς θέση ΑΤΛΑΣ'],
                'details_status' => ['completed' => 'Στοιχεία ολοκληρωμένα', 'pending' => 'Στοιχεία σε εκκρεμότητα'],
                'input_status' => ['completed' => 'Είσοδος: ολοκληρώθηκε', 'pending' => 'Είσοδος: εκκρεμεί', 'not_required' => 'Είσοδος: δεν απαιτείται'],
                'output_status' => ['completed' => 'Έξοδος: ολοκληρώθηκε', 'pending' => 'Έξοδος: εκκρεμεί', 'not_required' => 'Έξοδος: δεν απαιτείται'],
                'questionnaires_status' => ['completed' => 'Ερωτηματολόγια ολοκληρωμένα', 'student_pending' => 'Εκκρεμεί ερωτηματολόγιο φοιτητή', 'provider_pending' => 'Εκκρεμεί ερωτηματολόγιο φορέα', 'pending' => 'Εκκρεμεί ερωτηματολόγιο'],
                'document_status' => ['missing' => 'Έντυπο: δεν ανέβηκε', 'submitted' => 'Έντυπο: προς έγκριση', 'approved' => 'Έντυπο: εγκεκριμένο', 'rejected' => 'Έντυπο: απορρίφθηκε', 'not_required' => 'Έντυπο: δεν απαιτείται'],
                'email_status' => ['sent' => 'Email: στάλθηκε', 'not_sent' => 'Email: δεν στάλθηκε'],
            ];
            $activeFilterLabels = collect($filters)
                ->except(['document_type', 'email_type'])
                ->filter()
                ->map(fn ($value, $name) => $filterLabels[$name][$value] ?? $value);

            if ($filters['document_type'] !== '') {
                $activeFilterLabels->prepend('Έντυπο: '.$documentTypes->firstWhere('code', $filters['document_type'])?->title);
            }
            if ($filters['email_type'] !== '') {
                $activeFilterLabels->push('Email: '.$emailTypes[$filters['email_type']]);
            }
        @endphp

        <details class="border rounded mt-3" @if ($hasAdvancedFilters) open @endif>
            <summary class="fw-semibold p-3">
                Περισσότερα φίλτρα
                @if ($activeFilterLabels->isNotEmpty())
                    <span class="float-end d-inline-flex flex-wrap justify-content-end gap-1 ms-2">
                        @foreach ($activeFilterLabels as $label)
                            <span class="badge rounded-pill text-bg-primary">{{ $label }}</span>
                        @endforeach
                    </span>
                @endif
            </summary>
            <div class="border-top p-3">
                <div class="small fw-semibold text-uppercase text-muted mb-2">Διαδικασία</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4 col-xl-3">
                        <label for="student_status" class="form-label">Κατάσταση πρακτικής</label>
                        <select id="student_status" name="student_status" class="form-select">
                            <option value="">Όλες</option>
                            <option value="active" @selected($filters['student_status'] === 'active')>Ενεργή</option>
                            <option value="completed" @selected($filters['student_status'] === 'completed')>Ολοκληρωμένη</option>
                            <option value="cancelled" @selected($filters['student_status'] === 'cancelled')>Ακυρωμένη</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="date_stage" class="form-label">Χρονική κατάσταση</label>
                        <select id="date_stage" name="date_stage" class="form-select">
                            <option value="">Όλες</option>
                            <option value="before_start" @selected($filters['date_stage'] === 'before_start')>Πριν την έναρξη</option>
                            <option value="in_progress" @selected($filters['date_stage'] === 'in_progress')>Σε εξέλιξη</option>
                            <option value="after_end" @selected($filters['date_stage'] === 'after_end')>Μετά τη λήξη</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="weekly_hours" class="form-label">Ώρες ανά εβδομάδα</label>
                        <select id="weekly_hours" name="weekly_hours" class="form-select">
                            <option value="">Όλες</option>
                            <option value="20" @selected($filters['weekly_hours'] === '20')>20 ώρες</option>
                            <option value="40" @selected($filters['weekly_hours'] === '40')>40 ώρες</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="atlas_position" class="form-label">Θέση ΑΤΛΑΣ</label>
                        <select id="atlas_position" name="atlas_position" class="form-select">
                            <option value="">Όλες</option>
                            <option value="with" @selected($filters['atlas_position'] === 'with')>Με θέση</option>
                            <option value="without" @selected($filters['atlas_position'] === 'without')>Χωρίς θέση</option>
                        </select>
                    </div>
                </div>

                <div class="small fw-semibold text-uppercase text-muted mb-2">Συμπληρώσεις</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4 col-xl-3">
                        <label for="details_status" class="form-label">Στοιχεία φοιτητή / φορέα</label>
                        <select id="details_status" name="details_status" class="form-select">
                            <option value="">Όλα</option>
                            <option value="completed" @selected($filters['details_status'] === 'completed')>Ολοκληρωμένα</option>
                            <option value="pending" @selected($filters['details_status'] === 'pending')>Σε εκκρεμότητα</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="input_status" class="form-label">Απογραφικό εισόδου</label>
                        <select id="input_status" name="input_status" class="form-select">
                            <option value="">Όλα</option>
                            <option value="completed" @selected($filters['input_status'] === 'completed')>Ολοκληρώθηκε</option>
                            <option value="pending" @selected($filters['input_status'] === 'pending')>Εκκρεμεί</option>
                            <option value="not_required" @selected($filters['input_status'] === 'not_required')>Δεν απαιτείται</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="output_status" class="form-label">Απογραφικό εξόδου</label>
                        <select id="output_status" name="output_status" class="form-select">
                            <option value="">Όλα</option>
                            <option value="completed" @selected($filters['output_status'] === 'completed')>Ολοκληρώθηκε</option>
                            <option value="pending" @selected($filters['output_status'] === 'pending')>Εκκρεμεί</option>
                            <option value="not_required" @selected($filters['output_status'] === 'not_required')>Δεν απαιτείται</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="questionnaires_status" class="form-label">Ερωτηματολόγια</label>
                        <select id="questionnaires_status" name="questionnaires_status" class="form-select">
                            <option value="">Όλα</option>
                            <option value="completed" @selected($filters['questionnaires_status'] === 'completed')>Και τα δύο ολοκληρωμένα</option>
                            <option value="student_pending" @selected($filters['questionnaires_status'] === 'student_pending')>Εκκρεμεί του φοιτητή</option>
                            <option value="provider_pending" @selected($filters['questionnaires_status'] === 'provider_pending')>Εκκρεμεί του φορέα</option>
                            <option value="pending" @selected($filters['questionnaires_status'] === 'pending')>Εκκρεμεί τουλάχιστον ένα</option>
                        </select>
                    </div>
                </div>

                <div class="small fw-semibold text-uppercase text-muted mb-2">Έντυπα και επικοινωνία</div>
                <div class="row g-3">
                    <div class="col-md-4 col-xl-3">
                        <label for="document_type" class="form-label">Τύπος εντύπου</label>
                        <select id="document_type" name="document_type" class="form-select">
                            <option value="">Όλοι οι τύποι</option>
                            @foreach ($documentTypes as $documentType)
                                <option value="{{ $documentType->code }}" @selected($filters['document_type'] === $documentType->code)>
                                    {{ $documentType->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="document_status" class="form-label">Κατάσταση εντύπου</label>
                        <select id="document_status" name="document_status" class="form-select">
                            <option value="">Όλες</option>
                            <option value="missing" @selected($filters['document_status'] === 'missing')>Δεν έχει ανέβει</option>
                            <option value="submitted" @selected($filters['document_status'] === 'submitted')>Προς έγκριση</option>
                            <option value="approved" @selected($filters['document_status'] === 'approved')>Εγκεκριμένο</option>
                            <option value="rejected" @selected($filters['document_status'] === 'rejected')>Απορρίφθηκε</option>
                            <option value="not_required" @selected($filters['document_status'] === 'not_required')>Δεν απαιτείται</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="email_type" class="form-label">Τύπος email</label>
                        <select id="email_type" name="email_type" class="form-select">
                            <option value="">Όλοι οι τύποι</option>
                            @foreach ($emailTypes as $type => $title)
                                <option value="{{ $type }}" @selected($filters['email_type'] === $type)>{{ $title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-xl-3">
                        <label for="email_status" class="form-label">Κατάσταση email</label>
                        <select id="email_status" name="email_status" class="form-select">
                            <option value="">Όλα</option>
                            <option value="not_sent" @selected($filters['email_status'] === 'not_sent')>Δεν έχει σταλεί</option>
                            <option value="sent" @selected($filters['email_status'] === 'sent')>Έχει σταλεί</option>
                        </select>
                    </div>
                </div>
            </div>
        </details>
    </div>
</form>

@extends('layouts.portal', ['portalRole' => 'student', 'activePortalPage' => 'records'])

@section('title', $type === 'input' ? 'Απογραφικό δελτίο εισόδου' : 'Απογραφικό δελτίο εξόδου')

@section('portal-content')
    @php
        $title = $type === 'input' ? 'Απογραφικό δελτίο εισόδου' : 'Απογραφικό δελτίο εξόδου';
        $status = $apografiko?->status;
        $final = $legacyCompleted || in_array($status, ['completed', 'not_required', 'cancelled'], true);
        $processing = $status === 'processing';
    @endphp

    <div class="mb-4">
        <h1 class="h2 mb-1">{{ $title }}</h1>
        <div class="text-body-secondary">MIS: {{ $mis ?: 'δεν έχει οριστεί' }}</div>
    </div>

    @if (session('status'))
        <div class="alert alert-{{ session('status_type', 'success') }}">{{ session('status') }}</div>
    @endif

    @if ($legacyCompleted)
        <div class="alert alert-success">Το απογραφικό δελτίο έχει ολοκληρωθεί στην παλιά εφαρμογή.</div>
    @elseif ($apografiko)
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge {{ \App\Support\StatusBadge::className($status) }}">{{ \App\Support\StatusBadge::label($status) }}</span>
                    @if ($apografiko->deltio_id)
                        <span class="ms-2">ID δελτίου ΟΠΣ: <strong>{{ $apografiko->deltio_id }}</strong></span>
                    @endif
                    @if ($apografiko->error_message && $status === 'failed')
                        <div class="text-danger small mt-2">{{ $apografiko->error_message }}</div>
                    @endif
                </div>
                @if ($processing)
                    <form method="POST" action="{{ route('student.apografika.check', $type) }}">
                        @csrf
                        <button class="btn btn-warning">Έλεγχος αποτελέσματος</button>
                    </form>
                @elseif ($status === 'completed')
                    <a class="btn btn-outline-primary" href="{{ route('student.apografika.pdf', $type) }}" target="_blank">Προβολή PDF</a>
                @endif
            </div>
        </div>
    @endif

    @if (! $final && ! $processing && $status !== 'submitted')
        <div class="alert alert-info">
            Ελέγξτε τα στοιχεία σας πριν συνεχίσετε. Αν κάποιο στοιχείο είναι λανθασμένο, μην υποβάλετε το δελτίο και επικοινωνήστε με το Γραφείο Πρακτικής Άσκησης.
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Στοιχεία που θα χρησιμοποιηθούν</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><div class="small text-body-secondary">ΑΦΜ</div><strong>{{ $student->afm ?: 'Λείπει' }}</strong></div>
                    <div class="col-md-4"><div class="small text-body-secondary">Αριθμός Μητρώου</div><strong>{{ $student->AMF ?: 'Λείπει' }}</strong></div>
                    <div class="col-md-4"><div class="small text-body-secondary">Φύλο</div><strong>{{ config('praktiki_fields.select_options.fylo_rescom.'.$student->fylo_rescom, 'Λείπει') }}</strong></div>
                    <div class="col-md-4"><div class="small text-body-secondary">Ημερομηνία δελτίου</div><strong>{{ ($type === 'input' ? $student->startDate : $student->endDate)?->format('d/m/Y') ?: 'Λείπει' }}</strong></div>
                    <div class="col-md-4"><div class="small text-body-secondary">Περιφέρεια ΟΠΣ</div><strong>55</strong></div>
                    <div class="col-md-4"><div class="small text-body-secondary">Κωδικός συμμετέχοντα δικαιούχου</div><strong>{{ $student->AMF ?: 'Λείπει' }}</strong></div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('student.apografika.store', $type) }}">
            @csrf

            @if ($type === 'input')
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold">Απαντήσεις δελτίου εισόδου</div>
                    <div class="card-body p-0">
                        <div class="alert alert-warning text-dark rounded-0 border-0 py-2 mb-0">
                            <strong>Παρακαλούμε διαβάστε πάρα πολύ προσεκτικά τα ακόλουθα:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Διαβάστε πολύ προσεκτικά τις ερωτήσεις-επεξηγήσεις και απαντήστε σε όσα ερωτήματα δεν έχουν απαντηθεί, σημειώνοντας στο αντίστοιχο πεδίο ΝΑΙ.</li>
                                <li>Όλες οι ερωτήσεις διαθέτουν ως προεπιλογή το ΟΧΙ (εκτός από 2).</li>
                                <li>Απαντήστε στις παρακάτω ερωτήσεις, δεδομένου ότι η πρακτική σας άσκηση δεν έχει ξεκινήσει ακόμη. Π.χ. στην ερώτηση Γ θα δηλώσετε το μορφωτικό σας επίπεδο πριν την έναρξη της πρακτικής άσκησης.</li>
                                <li><strong>Μην ξεχάσετε να πατήσετε το κουμπί ολοκλήρωση στο τέλος.</strong></li>
                            </ul>
                        </div>

                        <div class="px-3 pt-3 pb-2">
                            <h2 class="h5 text-center alert alert-info text-dark py-2 mb-2">Α. Καθεστώς Εργασίας κατά την Είσοδο</h2>
                            <p class="mb-0 text-body-secondary">
                                Τα δεδομένα που αφορούν στην εργασιακή σας κατάσταση πριν την είσοδό σας στη δράση συλλέγονται από διοικητικές πηγές με βάση το ΑΦΜ σας. Με βάση αυτά θα γίνει η κατηγοριοποίησή σας στους αντίστοιχους δείκτες του ΕΚΤ+ που μετρούν ΑΝΕΡΓΟΥΣ, ΜΑΚΡΟΧΡΟΝΙΑ ΑΝΕΡΓΟΥΣ, ΑΠΑΣΧΟΛΟΥΜΕΝΟΥΣ/ΑΥΤΟΑΠΑΣΧΟΛΟΥΜΕΝΟΥΣ, ΟΙΚΟΝΟΜΙΚΑ ΜΗ ΕΝΕΡΓΟΥΣ.
                            </p>
                        </div>

                        <div class="px-3 pt-2 pb-1">
                            <h2 class="h5 text-center alert alert-info text-dark py-2 mb-2">Β. Συμμετοχή σε εκπαίδευση/κατάρτιση κατά την Είσοδο</h2>
                            <p class="small text-danger mb-1">Η κατηγορία Β έχει προσυμπληρωθεί για εσάς και οι απαντήσεις είναι ίδιες για όλους/ες τους/τις φοιτητές/τριες.</p>
                        </div>

                        @foreach ($inputTrainingQuestions as $id => $question)
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 px-3 py-2">
                                <div class="flex-grow-1 {{ $question->epipedo === 2 ? 'ms-md-4' : '' }}">
                                    <div class="p-2 bg-primary-subtle text-dark"><strong>{{ $question->kodikos_erotimatos }}</strong> — {{ $question->perigrafi }}</div>
                                    @if ($question->epexhghsh)<div class="small text-danger mt-1">{{ $question->epexhghsh }}</div>@endif
                                </div>
                                <div class="flex-shrink-0 text-nowrap text-lg-end" style="width: 9rem">
                                    @php($fixedAnswer = config('praktiki_apografika.input.fixed_answers.'.$id))
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" @checked($fixedAnswer === '5321') disabled>
                                        <span class="form-check-label">ΝΑΙ</span>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" @checked($fixedAnswer === '5322') disabled>
                                        <span class="form-check-label">ΟΧΙ</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="px-3 pt-2 pb-1">
                            <h2 class="h5 text-center alert alert-info text-dark py-2 mb-2">Γ. Μορφωτικό επίπεδο κατά την Είσοδο</h2>
                            <p class="small text-danger mb-1">
                                Υποχρεωτική απάντηση ΝΑΙ σε ΜΟΝΟ μία κατηγορία που αντιστοιχεί στο ανώτερο επίπεδο εκπαίδευσης που κατέχετε κατά την είσοδο στην πράξη. Αν <strong>είστε ακόμη φοιτητής/τρια</strong> και <strong>ΔΕΝ</strong> έχετε πτυχίο από άλλη σχολή, επιλέξτε <strong>Γ5</strong> ή <strong>Γ6</strong>.
                            </p>
                        </div>

                        @foreach ($educationQuestions as $id => $question)
                            @php($selectedEducation = (string) old('answers.education_level', $formAnswers['education_level']) === (string) $id)
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 px-3 py-2">
                                <label class="flex-grow-1 ms-md-4 p-2 bg-primary-subtle text-dark" for="education-{{ $id }}">
                                    <strong>{{ $question->kodikos_erotimatos }}</strong> — {{ $question->perigrafi }}
                                </label>
                                <div class="flex-shrink-0 text-nowrap text-lg-end" style="width: 9rem">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input @error('answers.education_level') is-invalid @enderror" type="radio" name="answers[education_level]" value="{{ $id }}" id="education-{{ $id }}" @checked($selectedEducation)>
                                        <label class="form-check-label" for="education-{{ $id }}">ΝΑΙ</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" @checked(! $selectedEducation) disabled>
                                        <span class="form-check-label">ΟΧΙ</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @error('answers.education_level')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror

                        <div class="px-3 pt-2 pb-1">
                            <h2 class="h5 text-center alert alert-info text-dark py-2 mb-2">Δ. Λοιποί κοινοί δείκτες εκροών ΕΚΤ+</h2>
                            <p class="small text-body-secondary mb-1">Οι ερωτήσεις αφορούν την κατάστασή σας πριν από την είσοδο στην πράξη.</p>
                        </div>

                        @foreach ($indicatorQuestions as $id => $question)
                            @php($indicatorValue = (string) old('answers.indicators.'.$id, $formAnswers['indicators'][$id] ?? ''))
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 px-3 py-2">
                                <div class="flex-grow-1">
                                    <div class="p-2 bg-primary-subtle text-dark"><strong>{{ $question->kodikos_erotimatos }}</strong> — {{ $question->perigrafi }}</div>
                                    @if ($question->epexhghsh)<div class="small text-danger mt-1">{{ $question->epexhghsh }}</div>@endif
                                </div>
                                <div class="flex-shrink-0 text-nowrap text-lg-end">
                                    @foreach (['5321' => 'ΝΑΙ', '5322' => 'ΟΧΙ', '5323' => 'ΔΕΝ ΑΠΑΝΤΩ'] as $value => $answerLabel)
                                        <div class="form-check form-check-inline me-2">
                                            <input class="form-check-input @error('answers.indicators.'.$id) is-invalid @enderror" type="radio" name="answers[indicators][{{ $id }}]" value="{{ $value }}" id="indicator-{{ $id }}-{{ $value }}" @checked($indicatorValue === $value)>
                                            <label class="form-check-label" for="indicator-{{ $id }}-{{ $value }}">{{ $answerLabel }}</label>
                                        </div>
                                    @endforeach
                                    @error('answers.indicators.'.$id)<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold">Απαντήσεις δελτίου εξόδου</div>
                    <div class="card-body p-0">
                        <div class="alert alert-warning text-dark rounded-0 border-0 py-2 mb-0">
                            <strong>Παρακαλούμε διαβάστε πάρα πολύ προσεκτικά τα ακόλουθα:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Οι ερωτήσεις Β, Β1, Β2, Β3, Β4 και Β5 έχουν απαντηθεί εκ των προτέρων. Δεν χρειάζεται να τις απαντήσετε.</li>
                                <li><strong>Μην ξεχάσετε να πατήσετε το κουμπί ολοκλήρωση στο τέλος.</strong></li>
                            </ul>
                        </div>

                        <div class="px-3 pt-3 pb-2">
                            <h2 class="h5 text-center alert alert-info text-dark py-2 mb-2">Α. Καθεστώς Εργασίας 4 εβδομάδες μετά την Έξοδο από την πράξη</h2>
                            <p class="mb-0 text-body-secondary">
                                Δεδομένα που αφορούν στην εργασιακή σας κατάσταση σε δύο χρονικές περιόδους μετά τη λήξη συμμετοχής σας στη δράση θα συλλεχθούν από διοικητικές πηγές ή έρευνες. Σε όσες δράσεις κατά την είσοδό σας καταγραφήκατε ως άνεργοι ή οικονομικά μη ενεργοί, μετά την έξοδό σας από τη δράση θα γίνει η κατηγοριοποίησή σας στους αντίστοιχους δείκτες του ΕΚΤ+ που μετρούν ΣΥΜΜΕΤΕΧΟΝΤΕΣ ΠΟΥ ΑΝΑΖΗΤΟΥΝ ΕΡΓΑΣΙΑ 4 εβδομάδες μετά την έξοδο και ΑΠΑΣΧΟΛΟΥΜΕΝΟΥΣ/ΑΥΤΟΑΠΑΣΧΟΛΟΥΜΕΝΟΥΣ 4 εβδομάδες και 6 μήνες μετά την έξοδο. Σε όσες δράσεις κατά την είσοδό σας καταγραφήκατε ως απασχολούμενοι/αυτοαπασχολούμενοι, οι αρμόδιες υπηρεσίες θα συλλέξουν δεδομένα για τη μέτρηση του δείκτη του ΕΚΤ+ που αφορά ΣΥΜΜΕΤΕΧΟΝΤΕΣ ΜΕ ΒΕΛΤΙΩΜΕΝΗ ΚΑΤΑΣΤΑΣΗ ΣΤΗΝ ΑΓΟΡΑ ΕΡΓΑΣΙΑΣ 6 μήνες μετά την έξοδο.
                            </p>
                        </div>

                        <div class="px-3 pt-2 pb-1">
                            <h2 class="h5 text-center alert alert-info text-dark py-2 mb-0">Β. Συμμετοχή σε εκπαίδευση/κατάρτιση 4 εβδομάδες μετά την Έξοδο από την πράξη</h2>
                        </div>

                        @foreach ($outputQuestions as $id => $question)
                            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 px-3 py-2">
                                <div class="flex-grow-1 {{ $question->epipedo === 2 ? 'ms-md-4' : '' }}">
                                    <div class="p-2 bg-primary-subtle text-dark">
                                        <strong>{{ $question->kodikos_erotimatos }}</strong> — {{ $question->perigrafi }}
                                    </div>
                                    @if ($question->epexhghsh)
                                        <div class="small text-danger mt-2">{{ $question->epexhghsh }}</div>
                                    @endif
                                </div>
                                <div class="flex-shrink-0 text-nowrap text-lg-end" style="width: 9rem">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="output-{{ $id }}-yes" @checked(config('praktiki_apografika.output.fixed_answers.'.$id) === '5321') disabled>
                                        <label class="form-check-label" for="output-{{ $id }}-yes">ΝΑΙ</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="output-{{ $id }}-no" @checked(config('praktiki_apografika.output.fixed_answers.'.$id) === '5322') disabled>
                                        <label class="form-check-label" for="output-{{ $id }}-no">ΟΧΙ</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="px-3 pt-2 pb-3">
                            <h2 class="h5 text-center alert alert-info text-dark py-2 mb-2">Γ. Απόκτηση επαγγελματικού προσόντος</h2>
                            <p class="mb-0 text-body-secondary">
                                Για το δείκτη που μετρά ΣΥΜΜΕΤΕΧΟΝΤΕΣ ΠΟΥ ΑΠΟΚΤΟΥΝ ΤΥΠΙΚΟ ΕΠΑΓΓΕΛΜΑΤΙΚΟ ΠΡΟΣΟΝ μετά από εξετάσεις πιστοποίησης, τα αντίστοιχα δεδομένα συλλέγονται από το Δικαιούχο που έχει την ευθύνη υλοποίησης της δράσης και σύμφωνα με τις οδηγίες της αρμόδιας Διαχειριστικής Αρχής. Ο δείκτης λαμβάνει τιμή 1 όταν ο συμμετέχων/η συμμετέχουσα έχει αποκτήσει την αντίστοιχη εξειδίκευση/πιστοποίηση μετά από εξετάσεις που έχουν πραγματοποιηθεί είτε στο πλαίσιο υλοποίησης της πράξης είτε το διάστημα αμέσως μετά τη λήξη συμμετοχής του στην πράξη και έως τέσσερις (4) εβδομάδες μετά.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex flex-wrap gap-2 justify-content-end mb-5">
                @if ($type === 'input')
                    <button class="btn btn-outline-primary" name="submission" value="draft">Προσωρινή αποθήκευση</button>
                @endif
                <button class="btn btn-success" name="submission" value="final" onclick="return confirm('Η οριστική υποβολή θα σταλεί στο δοκιμαστικό ΟΠΣ. Θέλετε να συνεχίσετε;')">Οριστική υποβολή στο ΟΠΣ</button>
            </div>
        </form>
    @endif
@endsection

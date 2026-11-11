@extends('layouts.portal', ['portalRole' => 'student', 'activePortalPage' => $context === 'student' ? 'student-details' : 'provider-details'])

{{-- Η ίδια φόρμα χρησιμοποιείται για τα στοιχεία φοιτητή και φορέα. --}}

@section('title', $title)

@section('portal-content')
    <div class="mb-4">
        <h1 class="h3 mb-1">{{ $title }}</h1>
        <p class="text-body-secondary mb-0">{{ $description }}</p>
    </div>

    @if (! $canEdit)
        <div class="alert alert-info">Η φόρμα έχει υποβληθεί οριστικά. Για διόρθωση επικοινωνήστε με το Γραφείο Πρακτικής Άσκησης.</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-4">
            @if ($readOnlyFields !== [])
            <div class="accordion mb-4" id="officeFieldsAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="officeFieldsHeading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#officeFields" aria-expanded="false" aria-controls="officeFields">
                            Στοιχεία που έχουν καταχωριστεί από το Γραφείο Πρακτικής Άσκησης
                        </button>
                    </h2>
                    <div id="officeFields" class="accordion-collapse collapse" aria-labelledby="officeFieldsHeading">
                        <div class="accordion-body">
                            <div class="row g-3">
                                @foreach ($readOnlyFields as $field => $label)
                                    <div class="col-12 col-md-6">
                                        <label class="form-label mb-1" for="readonly-{{ $field }}">{{ $label }}</label>
                                        <input id="readonly-{{ $field }}" class="form-control bg-body-secondary text-body" value="{{ filled($student->{$field}) ? $student->{$field} : '—' }}" disabled>
                                        <div class="form-text">Το στοιχείο έχει καταχωριστεί από το Γραφείο Πρακτικής και δεν αλλάζει.</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route($route) }}">
                @csrf
                @method('PATCH')
                <div class="accordion" id="editableFieldsAccordion">
                    @foreach ($fieldGroups as $groupTitle => $groupFields)
                        @php
                            $groupId = 'editableFieldsGroup'.$loop->index;
                        @endphp
                        <div class="accordion-item mb-3 border rounded-3 overflow-hidden">
                            <h2 class="accordion-header" id="{{ $groupId }}Heading">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#{{ $groupId }}" aria-expanded="true" aria-controls="{{ $groupId }}">
                                    {{ $groupTitle }}
                                </button>
                            </h2>
                            <div id="{{ $groupId }}" class="accordion-collapse collapse show" aria-labelledby="{{ $groupId }}Heading">
                                <div class="accordion-body">
                                    <div class="row g-3">
                    @foreach ($groupFields as $field)
                        @php
                            $label = $fields[$field];
                            $item = old($field, $record->{$field} ?? null);
                            $type = config('praktiki_fields.field_types.'.$field, 'text');
                            $options = $fieldOptions[$field] ?? [];
                            if ($field === 'wresPerWeek' && $student->fundingCode() === 'Espa') {
                                $type = 'select';
                            }
                            $displayItem = $type === 'date' && $item instanceof \Carbon\CarbonInterface ? $item->format('Y-m-d') : $item;
                            $item = $field === 'numMhnes' && is_numeric($item) ? (string) (float) $item : $item;
                            $displayItem = $field === 'numMhnes' ? $item : $displayItem;
                            $wide = in_array($type, ['textarea', 'city'], true) || in_array($field, ['address_odos', 'workDaysDescription'], true);
                            $wide=true;
                            $uppercase = in_array($field, config('praktiki_fields.uppercase_fields'), true);
                            $help = $fieldHelp[$field] ?? null;
                            $isEspaCalculation = $student->fundingCode() === 'Espa'
                                && in_array($field, ['payPerMonth', 'payTotal', 'doseis_count'], true);
                        @endphp
                        <div class="{{ $wide ? 'col-12' : 'col-md-6' }} position-relative">
                            <label class="form-label mb-1" for="{{ $field }}">{{ $label }}</label>
                            @if ($help)
                                <div class="form-text mt-0 mb-1">{{ $help }}</div>
                            @endif
                            @if ($type === 'textarea')
                                <textarea class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" rows="4"
                                          @if ($uppercase) oninput="this.value = this.value.toUpperCase()" @endif @disabled(! $canEdit)>{{ $displayItem }}</textarea>
                            @elseif ($type === 'select')
                                <select class="form-select @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" @disabled(! $canEdit)>
                                    <option value="">— Επιλέξτε —</option>
                                    @foreach ($options as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $item === (string) $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif ($type === 'city')
                                <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}-search" type="search"
                                       value="{{ $cityLabels[$field] ?? '' }}" autocomplete="off" placeholder="Γράψτε τουλάχιστον 2 χαρακτήρες"
                                       data-city-search data-target="{{ $field }}" data-results="{{ $field }}-results"
                                       data-url="{{ route('student.rescom.cities') }}" @disabled(! $canEdit)>
                                <input id="{{ $field }}" type="hidden" name="{{ $field }}" value="{{ $item }}" @disabled(! $canEdit)>
                                <div id="{{ $field }}-results" class="list-group position-absolute z-3"></div>
                            @else
                                <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ $displayItem }}"
                                       @if ($uppercase) oninput="this.value = this.value.toUpperCase()" @endif
                                       @if ($field === 'wresPerDays') min="1" max="8" @endif
                                       @if ($field === 'wresPerWeek') min="1" max="40" @endif
                                       @if (in_array($field, ['payPerMonth', 'payTotal', 'numMhnes'], true)) step="0.01" @endif
                                       @readonly($isEspaCalculation) @disabled(! $canEdit)>
                            @endif
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($canEdit)
                    <div class="alert alert-warning mt-4 mb-3">Με την οριστική υποβολή δηλώνετε ότι τα στοιχεία είναι ορθά και ίδια με τα επίσημα έγγραφά σας.</div>
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-primary" type="submit" name="submission" value="draft">Προσωρινή αποθήκευση</button>
                        <button class="btn btn-primary" type="submit" name="submission" value="final">Οριστική υποβολή</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/rescom-city-search.js') }}" defer></script>
    @if ($student->fundingCode() === 'Espa')
        <script src="{{ asset('js/student-compensation.js') }}" defer></script>
    @endif
@endpush

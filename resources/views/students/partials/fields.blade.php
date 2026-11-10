<form method="POST" action="{{ route('students.section.update', ['student' => $student, 'section' => $section]) }}">
    @csrf
    @method('PATCH')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
            <div>
                <h2 class="h5 mb-1">{{ $title }}</h2>
                @isset($subtitle)
                    <div class="small text-body-secondary">{{ $subtitle }}</div>
                @endisset
            </div>
            <button class="btn btn-primary" type="submit" @disabled(! $canEdit)>Αποθήκευση</button>
        </div>
        <div class="card-body p-3 p-lg-4">
            @foreach (($groups ?? [null => array_keys($fields)]) as $groupTitle => $groupFields)
                <section class="border rounded-3 p-3 mb-3">
                    @if ($groupTitle)
                        <h3 class="h6 text-primary border-bottom pb-2 mb-3">{{ $groupTitle }}</h3>
                    @endif

                    <div class="row g-3">
                    @foreach ($groupFields as $field)
                        @continue(! array_key_exists($field, $fields))
                    @php
                        $label = $fields[$field];
                        $currentItem = $section === 'office'
                            ? ($field === 'status' ? $student->status : $student->action($field)?->status)
                            : $record?->{$field};
                        $item = old($field, $currentItem);
                        $type = config('praktiki_fields.field_types.'.$field, 'text');
                        $item = $field === 'diakoptomeno' && is_bool($item) ? (int) $item : $item;
                        $item = $field === 'numMhnes' && is_numeric($item) ? (string) (float) $item : $item;
                        $isReadOnly = in_array($field, [
                            'praxh_year', 'questionnaire_version', 'address_odos_polh',
                            'f_perioxh', 'f_topos_ekt_prakt',
                        ], true);
                        $displayItem = $type === 'date' && $item instanceof \Carbon\CarbonInterface ? $item->format('Y-m-d') : $item;
                        $options = $fieldOptions[$field] ?? [];
                        $hasCurrentOption = collect(array_keys($options))->contains(fn ($value) => (string) $value === (string) $item);
                        $columnClass = $type === 'textarea' || in_array($field, ['notes', 'address_odos', 'workDaysDescription'], true)
                            ? 'col-12'
                            : 'col-md-6 col-xl-4';
                        $minimum = config('praktiki_fields.field_minimums.'.$field);
                        $help = $fieldHelp[$field] ?? null;
                    @endphp
                    <div class="{{ $columnClass }} position-relative">
                        <label for="{{ $section }}-{{ $field }}" class="form-label small fw-semibold text-body-secondary mb-1">
                            {{ $label }}
                            @if ($help)
                                <button class="btn btn-sm border-0 p-0 text-primary" type="button" data-bs-toggle="tooltip" data-bs-title="{{ $help }}" aria-label="Πληροφορίες για {{ $label }}">ⓘ</button>
                            @endif
                        </label>

                        @if ($isReadOnly)
                            <div id="{{ $section }}-{{ $field }}" class="form-control-plaintext border-bottom fw-semibold">{{ filled($displayItem) ? $displayItem : '—' }}</div>
                        @elseif ($type === 'textarea')
                            <textarea id="{{ $section }}-{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" rows="3" @disabled(! $canEdit)>{{ $displayItem }}</textarea>
                        @elseif ($type === 'select')
                            <select id="{{ $section }}-{{ $field }}" name="{{ $field }}" class="form-select @error($field) is-invalid @enderror" @disabled(! $canEdit || $isReadOnly)>
                                @if ($field !== 'diakoptomeno')
                                    <option value="">— Επιλέξτε —</option>
                                @endif
                                @if (filled($item) && ! $hasCurrentOption)
                                    <option value="{{ $item }}" selected>{{ $item }} (παλιά τιμή)</option>
                                @endif
                                @foreach ($options as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected((string) $item === (string) $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @elseif ($type === 'city')
                            <input id="{{ $section }}-{{ $field }}-search" type="search" class="form-control @error($field) is-invalid @enderror"
                                   value="{{ $cityLabels[$field] ?? '' }}" placeholder="Γράψτε τουλάχιστον 2 χαρακτήρες"
                                   autocomplete="off" data-city-search data-target="{{ $section }}-{{ $field }}"
                                   data-results="{{ $section }}-{{ $field }}-results" data-url="{{ route('rescom.cities') }}"
                                   @disabled(! $canEdit)>
                            <input id="{{ $section }}-{{ $field }}" type="hidden" name="{{ $field }}" value="{{ $item }}" @disabled(! $canEdit)>
                            <div id="{{ $section }}-{{ $field }}-results" class="list-group position-absolute z-3"></div>
                        @else
                            <input id="{{ $section }}-{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                                   class="form-control @error($field) is-invalid @enderror" value="{{ $displayItem }}"
                                   @if ($minimum !== null) min="{{ $minimum }}" @endif
                                   @if ($field === 'wresPerDays') max="8" @endif
                                   @if ($field === 'wresPerWeek') max="40" @endif
                                   @if (in_array($field, ['payPerMonth', 'payTotal', 'numMhnes'], true)) step="0.01" @endif
                                   @disabled(! $canEdit || $isReadOnly)>
                        @endif

                        @error($field)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endforeach
                    </div>
                </section>
            @endforeach

            <div class="d-flex justify-content-end pt-2">
                <button class="btn btn-primary" type="submit" @disabled(! $canEdit)>Αποθήκευση</button>
            </div>
        </div>
    </div>
</form>

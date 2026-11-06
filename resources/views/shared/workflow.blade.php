<div class="accordion" id="{{ $accordionId }}">
@foreach ($sections as $section)
    <div class="accordion-item mb-3 shadow-sm">
        <div class="accordion-header d-flex align-items-stretch">
            <button class="accordion-button collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}-{{ strtolower($section['code']) }}">
                <span class="fw-semibold me-2">{{ $section['title'] }}</span>
                <span class="badge {{ \App\Support\StatusBadge::className($section['status']) }}">{{ $section['status'] }}</span>
            </button>
        </div>
        <div id="{{ $accordionId }}-{{ strtolower($section['code']) }}" class="accordion-collapse collapse" data-bs-parent="#{{ $accordionId }}">
            <div class="list-group list-group-flush">
                @forelse ($section['rows'] as $row)
                    @include('student.partials.action', $row + [
                        'currentRole' => $currentRole,
                        'showActionControls' => $showActionControls ?? true,
                    ])
                @empty
                    <div class="list-group-item small text-secondary py-3">Δεν απαιτείται κάποια ενέργεια από αυτή την πρακτική.</div>
                @endforelse
            </div>
        </div>
    </div>
@endforeach
</div>

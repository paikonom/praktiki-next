<div class="{{ ($flat ?? false) ? 'd-grid gap-3' : 'accordion shadow-sm' }}" id="{{ $accordionId }}">
    @foreach ($internshipDetailGroups as $index => $detailGroup)
        @php($itemId = $accordionId.'-'.$index)
        <div class="{{ ($flat ?? false) ? 'card border-0 shadow-sm' : 'accordion-item' }}">
            @if ($flat ?? false)
                <h2 class="h5 card-header bg-white py-3">{{ $detailGroup['title'] }}</h2>
            @else
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $itemId }}" aria-expanded="false" aria-controls="{{ $itemId }}">
                        {{ $detailGroup['title'] }}
                    </button>
                </h2>
            @endif
            <div @unless ($flat ?? false) id="{{ $itemId }}" class="accordion-collapse collapse" data-bs-parent="#{{ $accordionId }}" @endunless>
                <div class="accordion-body">
                    <div class="row g-3">
                        @foreach ($detailGroup['details'] as $detail)
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100 d-flex justify-content-between align-items-start gap-3 {{ $detail['missing'] ? 'border-danger bg-danger-subtle' : 'bg-body-tertiary' }}">
                                    <div class="small text-body-secondary">{{ $detail['label'] }}</div>
                                    @if (is_string($detail['value']) && str_starts_with($detail['value'], 'http'))
                                        <a class="fw-semibold text-break text-end" href="{{ $detail['value'] }}" target="_blank" rel="noopener">Προβολή</a>
                                    @else
                                        <div class="fw-semibold text-break text-end">{{ $detail['value'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@props(['value', 'plain' => false, 'emptyMarker' => true])

@if (filled($value))
    @if ($plain)
        <span>{{ $value }}</span>
    @else
        <strong>{{ $value }}</strong>
    @endif
@elseif ($emptyMarker)
    <strong style="color: #c00; font-weight: bold;">!!!IS EMPTY!!!</strong>
@endif

@props(['value', 'plain' => false])

@if (filled($value))
    @if ($plain)
        <span>{{ $value }}</span>
    @else
        <strong>{{ $value }}</strong>
    @endif
@else
    <strong style="color: #c00; font-weight: bold;">!!!IS EMPTY!!!</strong>
@endif

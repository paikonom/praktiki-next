@props(['value'])

@if (filled($value))
    <strong>{{ $value }}</strong>
@else
    <strong class="missing">!!!IS EMPTY!!!</strong>
@endif

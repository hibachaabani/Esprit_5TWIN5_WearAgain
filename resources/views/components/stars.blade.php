@props(['note' => 0])

@php
    $note = (float) $note;
    $arrondie = (int) round($note);
@endphp

<span class="stars" title="Note : {{ number_format($note, 1) }}/5">
    @for ($i = 1; $i <= 5; $i++)
        {{ $i <= $arrondie ? '★' : '☆' }}
    @endfor
    <span class="sr-only">Note de {{ number_format($note, 1) }} sur 5</span>
</span>

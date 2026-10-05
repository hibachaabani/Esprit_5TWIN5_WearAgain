@props(['point'])

@php
    $taux = $point->tauxRemplissage();
    $niveau = $taux >= 100 ? 'plein' : ($taux >= 75 ? 'haut' : 'ok');
@endphp

<div {{ $attributes->class(['jauge', "jauge-{$niveau}"]) }}>
    <div class="jauge-barre" role="progressbar" aria-label="Remplissage" aria-valuenow="{{ $taux }}" aria-valuemin="0" aria-valuemax="100">
        <span style="width: {{ $taux }}%"></span>
    </div>
    <p class="small muted">
        <strong>{{ $point->placesRestantes() }}</strong> place(s) libre(s) sur {{ $point->capacite }} · {{ $taux }} % rempli
    </p>
</div>

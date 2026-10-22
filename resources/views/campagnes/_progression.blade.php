{{-- Barre de progression d'une campagne : dépôts non annulés pendant la période / objectif. --}}
@php
    $collectes = $campagne->depotsCollectes();
    $pourcent = $campagne->objectif > 0 ? (int) min(100, round($collectes * 100 / $campagne->objectif)) : 0;
@endphp
<div class="progression jauge" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pourcent }}"
     aria-label="Progression de la campagne {{ $campagne->titre }}">
    <div class="jauge-barre"><span style="width: {{ $pourcent }}%"></span></div>
    <p class="small"><strong>{{ $collectes }}</strong> / {{ $campagne->objectif }} vêtements · {{ $pourcent }} %</p>
</div>

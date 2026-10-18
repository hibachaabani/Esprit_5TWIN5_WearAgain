{{--
| Avis de l'IA sur un don proposé (tri intelligent des dons reçus) : score, conseil et raisons.
| Attend $avis (TriDonsIA::evaluer) et, en option, $detaille pour afficher toutes les raisons.
--}}
<div @class(['avis-ia', 'avis-ia-'.$avis['conseil'], 'avis-ia-detaille' => $detaille ?? false])>
    <span class="avis-ia-score" title="Correspondance avec vos besoins">🎯 {{ $avis['score'] }} %</span>
    <strong>{{ \App\Services\IA\Dons\TriDonsIA::CONSEILS[$avis['conseil']] }}</strong>
    @if ($detaille ?? false)
        <ul>
            @foreach ($avis['raisons'] as $raison)
                <li>{{ $raison }}</li>
            @endforeach
        </ul>
    @else
        <span class="avis-ia-raison">{{ $avis['raisons'][0] ?? '' }}</span>
    @endif
</div>

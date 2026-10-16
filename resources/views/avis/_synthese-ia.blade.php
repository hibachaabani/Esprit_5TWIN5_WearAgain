@php
    /** @var array $synthese */
    $labels = ['delai' => 'Délai', 'prix' => 'Prix', 'qualite' => 'Qualité', 'accueil' => 'Accueil'];
    $aspects = array_filter($synthese['aspects'] ?? [], static fn ($score) => $score !== null);
@endphp

<section class="ia-panel ia-avis">
    <div class="ia-panel-head">
        <span class="ia-badge">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 3l1.9 4.6L18.5 9l-4.6 1.4L12 15l-1.9-4.6L5.5 9l4.6-1.4L12 3z" />
                <path d="M18 15l.9 2.1L21 18l-2.1.9L18 21l-.9-2.1L15 18l2.1-.9L18 15z" />
            </svg>
            Analyse IA des avis
        </span>
        <span class="ia-source">{{ ($synthese['source'] ?? 'local') === 'ia' ? 'Modèle IA' : 'Moteur local' }}</span>
    </div>

    @if (($synthese['nombre_avis'] ?? 0) === 0)
        <p class="ia-empty">{{ $synthese['verdict'] }}</p>
    @else
        <p class="ia-verdict">{{ $synthese['verdict'] }}</p>

        @if ($aspects !== [])
            <dl class="ia-aspects">
                @foreach ($aspects as $cle => $score)
                    <div class="ia-aspect">
                        <div class="ia-aspect-top">
                            <dt>{{ $labels[$cle] ?? $cle }}</dt>
                            <dd>{{ number_format((float) $score, 1, ',', ' ') }}/5</dd>
                        </div>
                        <div class="ia-aspect-bar" role="img" aria-label="{{ $labels[$cle] ?? $cle }} : {{ number_format((float) $score, 1, ',', ' ') }} sur 5">
                            <span style="width: {{ (float) $score / 5 * 100 }}%"></span>
                        </div>
                    </div>
                @endforeach
            </dl>
        @endif

        @if (! empty($synthese['points_forts']) || ! empty($synthese['points_faibles']))
            <div class="ia-points">
                @if (! empty($synthese['points_forts']))
                    <div class="ia-points--forts">
                        <h4>Points forts</h4>
                        <ul>
                            @foreach ($synthese['points_forts'] as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (! empty($synthese['points_faibles']))
                    <div class="ia-points--faibles">
                        <h4>Points à améliorer</h4>
                        <ul>
                            @foreach ($synthese['points_faibles'] as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    @endif
</section>

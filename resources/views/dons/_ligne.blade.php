{{--
| Une ligne de liste de dons, courte : le vêtement et l'autre partie, puis une seule information utile selon l'étape
| (message à l'arrivée, rendez-vous une fois accepté, issue une fois terminé).
| Attend $don et $pourAssociation (vrai dans « Dons reçus », faux dans « Mes dons »).
--}}
<article class="card don-row">
    <div class="don-row-info">
        <h3>
            <a href="{{ route('dons.show', $don) }}">{{ $don->vetement_titre }}</a>
            @if (in_array($don->statut, ['remis', 'refuse', 'annule'], true))
                <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
            @endif
            @if ($don->non_lus_count)
                <span class="non-lus" title="Nouveaux messages">{{ $don->non_lus_count }} ✉</span>
            @endif
        </h3>
        <p class="don-row-qui">
            {{ $pourAssociation ? $don->donateur?->name : $don->association?->nom }}
            @if ($don->besoin)
                <span class="etiquette-besoin">{{ $don->besoin->titre }}</span>
            @endif
        </p>

        @switch($don->statut)
            @case('propose')
                @if ($pourAssociation && $don->avis_ia)
                    @include('dons._avis_ia', ['avis' => $don->avis_ia])
                @endif
                @if ($pourAssociation)
                    <p class="don-row-detail">
                        {{ \App\Models\Don::LIBELLES_MODE[$don->mode] }} · proposé {{ $don->created_at->diffForHumans() }}
                        @if ($don->message) · « {{ Str::limit($don->message, 80) }} » @endif
                    </p>
                @else
                    <p class="don-row-detail">⏳ En attente de la réponse de l'association · proposé {{ $don->created_at->diffForHumans() }}</p>
                @endif
                @break
            @case('accepte')
                <p class="don-row-detail don-row-rdv">
                    @if ($don->date_remise)
                        📅 <strong>{{ $don->date_remise->translatedFormat('D d/m à H\hi') }}</strong> · {{ $don->lieu_remise }}
                    @else
                        📅 Rendez-vous à convenir dans la discussion
                    @endif
                </p>
                @break
            @case('remis')
                <p class="don-row-detail">Remis le {{ $don->date_don?->format('d/m/Y') }}</p>
                @break
            @case('refuse')
                <p class="don-row-detail">Motif : {{ $don->motif_refus ?? 'non précisé' }}</p>
                @break
            @case('annule')
                <p class="don-row-detail">Annulé{{ $don->cloture_le ? ' le '.$don->cloture_le->format('d/m/Y') : '' }}</p>
                @break
        @endswitch

        @if ($pourAssociation)
            @include('dons._traitement')
        @endif
    </div>

    <div class="don-row-actions">
        <a href="{{ route('dons.show', $don) }}" class="btn btn-ghost small">Ouvrir</a>
    </div>
</article>

@php($suivis = $reparation->suivis)
@if ($suivis->isNotEmpty())
<section class="timeline-wrap">
    <h2 class="{{ $titreClasse ?? 'h4' }}">Suivi de la réparation</h2>
    <ol class="timeline">
        @foreach ($suivis as $suivi)
            <li class="timeline-item">
                <div class="timeline-head">
                    <strong>{{ $suivi->statutLabel() }}</strong>
                    <time>{{ optional($suivi->created_at)->format('d/m/Y H:i') }}</time>
                </div>
                <p class="small muted">
                    @if ($suivi->ancienStatutLabel())
                        {{ $suivi->ancienStatutLabel() }} → {{ $suivi->statutLabel() }}
                    @else
                        Création de la demande
                    @endif
                    @if ($suivi->auteur) · par {{ $suivi->auteur->name }} @endif
                </p>
                @if ($suivi->commentaire)
                    <p class="small">{{ $suivi->commentaire }}</p>
                @endif
            </li>
        @endforeach
    </ol>
</section>
@endif

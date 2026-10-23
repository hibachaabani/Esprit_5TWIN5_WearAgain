{{-- Carte d'un besoin (liste des besoins, accueil) : urgence, association, ce qui manque, progression. --}}
<article class="besoin-carte">
    <p class="besoin-meta-ligne">
        <span class="point-urgence urgence-{{ $besoin->urgence }}">● {{ \App\Models\Besoin::URGENCES[$besoin->urgence] }}</span>
        · {{ \App\Models\Association::CATEGORIES[$besoin->categorie] ?? $besoin->categorie }}
        @if ($besoin->cleStatut() !== 'ouvert')
            <span class="besoin-statut besoin-statut-{{ $besoin->cleStatut() }}">{{ $besoin->libelleStatut() }}</span>
        @endif
    </p>
    {{-- Le titre couvre toute la carte : un clic n'importe où ouvre le besoin. --}}
    <h3><a href="{{ route('besoins.show', $besoin) }}" class="lien-carte">{{ $besoin->titre }}</a></h3>
    <p class="besoin-asso">{{ $besoin->association->nom }}{{ $besoin->association->ville ? ' · '.$besoin->association->ville : '' }}</p>

    <div class="besoin-manque">
        @if ($besoin->estComplet())
            <strong>Objectif atteint</strong> grâce aux dons en route
        @else
            Il en manque <strong>{{ $besoin->quantiteRestante() }}</strong> sur {{ $besoin->quantite }}
        @endif
    </div>
    <div class="progression-barre"><span style="width: {{ $besoin->progression() }}%"></span><span class="en-route" style="width: {{ $besoin->progressionEnRoute() }}%"></span></div>

    <div class="besoin-carte-pied">
        <span class="small muted">{{ $besoin->date_limite ? 'Avant le '.$besoin->date_limite->format('d/m') : 'Sans date limite' }}</span>
        @if ($besoin->accepteDesDons())
            @particulier
                <a href="{{ route('dons.create', ['besoin_id' => $besoin->id]) }}" class="btn btn-primary small au-dessus">Je donne</a>
            @endparticulier
        @endif
    </div>
</article>

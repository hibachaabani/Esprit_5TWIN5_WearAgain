@php
    /** @var \App\Models\Reparation $reparation */
    $prix = $reparation->ia_prix_min !== null
        ? number_format((float) $reparation->ia_prix_min, 2, ',', ' ').' – '.number_format((float) $reparation->ia_prix_max, 2, ',', ' ').' DT'
        : '—';
@endphp

@if ($reparation->aDiagnosticIA())
    <section class="ia-panel">
        <div class="ia-panel-head">
            <span class="ia-badge">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3l1.9 4.6L18.5 9l-4.6 1.4L12 15l-1.9-4.6L5.5 9l4.6-1.4L12 3z" />
                    <path d="M18 15l.9 2.1L21 18l-2.1.9L18 21l-.9-2.1L15 18l2.1-.9L18 15z" />
                </svg>
                Diagnostic IA
            </span>
            <span class="ia-source">{{ $reparation->ia_source === 'ia' ? 'Modèle IA' : 'Moteur local' }}</span>
        </div>

        <div class="ia-grid">
            <div class="ia-cell">
                <dt>Catégorie</dt>
                <dd>{{ $reparation->ia_categorie }}</dd>
            </div>
            <div class="ia-cell">
                <dt>Gravité</dt>
                <dd><span class="ia-gravite ia-gravite--{{ $reparation->ia_gravite }}">{{ $reparation->iaGraviteLabel() }}</span></dd>
            </div>
            <div class="ia-cell">
                <dt>Fourchette estimée</dt>
                <dd>{{ $prix }}</dd>
            </div>
            <div class="ia-cell">
                <dt>Délai estimé</dt>
                <dd>{{ $reparation->ia_delai_jours }} jour{{ $reparation->ia_delai_jours > 1 ? 's' : '' }}</dd>
            </div>
        </div>

        @if (! empty($reparation->ia_competences))
            <p class="ia-line"><strong>Compétences requises :</strong> {{ implode(', ', $reparation->ia_competences) }}</p>
        @endif
        @if (! empty($reparation->ia_materiaux))
            <p class="ia-line"><strong>Matériaux :</strong> {{ implode(', ', $reparation->ia_materiaux) }}</p>
        @endif
        @if ($reparation->ia_recommandation)
            <p class="ia-line ia-reco">{{ $reparation->ia_recommandation }}</p>
        @endif
    </section>
@endif

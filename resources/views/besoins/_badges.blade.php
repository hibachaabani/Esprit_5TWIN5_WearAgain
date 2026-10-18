{{-- Urgence et statut d'un besoin (« Expiré » après la date limite, « Complet » quand l'objectif est couvert). --}}
<span class="urgence urgence-{{ $besoin->urgence }}">{{ \App\Models\Besoin::URGENCES[$besoin->urgence] }}</span>
<span class="besoin-statut besoin-statut-{{ $besoin->cleStatut() }}">{{ $besoin->libelleStatut() }}</span>

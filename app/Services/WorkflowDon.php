<?php

namespace App\Services;

use App\Models\Don;
use App\Models\User;
use App\Notifications\DonMisAJour;
use Illuminate\Support\Facades\DB;

/**
 * Point d'entrée unique pour faire avancer un don :
 * mise à jour du statut, horodatage, événement dans le fil et e-mail à l'autre participant.
 *
 *   proposé ──(association)──► accepté (rendez-vous) ──(association)──► remis
 *      ├──(association)──► refusé (motif)
 *      └──(donateur)──► annulé ◄── (aussi depuis « accepté »)
 */
class WorkflowDon
{
    public function proposer(Don $don): void
    {
        $don->ajouterEvenement('Don proposé'.($don->besoin ? " pour le besoin « {$don->besoin->titre} »" : '').'.');
        $this->prevenir($don->association->user, $don);
    }

    /** @param  array{date_remise: string, lieu_remise: string, consigne?: ?string}  $remise */
    public function accepter(Don $don, array $remise): void
    {
        DB::transaction(function () use ($don, $remise) {
            $don->update([
                'statut' => 'accepte',
                'date_remise' => $remise['date_remise'],
                'lieu_remise' => $remise['lieu_remise'],
                'consigne' => $remise['consigne'] ?? null,
                'accepte_le' => now(),
            ]);

            $don->ajouterEvenement('Don accepté. Remise prévue le '.$don->date_remise->format('d/m/Y à H:i')
                ." : {$don->lieu_remise}.".($don->consigne ? " Consigne : {$don->consigne}" : ''));
        });

        $this->prevenir($don->donateur, $don);
    }

    public function refuser(Don $don, string $motif): void
    {
        DB::transaction(function () use ($don, $motif) {
            $don->update(['statut' => 'refuse', 'motif_refus' => $motif, 'cloture_le' => now()]);
            $don->ajouterEvenement("Don refusé. Motif : {$motif}");
        });

        $this->prevenir($don->donateur, $don);
    }

    public function remettre(Don $don): void
    {
        DB::transaction(function () use ($don) {
            $don->update(['statut' => 'remis', 'date_don' => now()->toDateString(), 'cloture_le' => now()]);
            $don->ajouterEvenement('Vêtement remis à l\'association. Merci !');
            $don->besoin?->verifierObjectif();
        });

        $this->prevenir($don->donateur, $don);
    }

    public function annuler(Don $don): void
    {
        DB::transaction(function () use ($don) {
            $don->update(['statut' => 'annule', 'cloture_le' => now()]);
            $don->ajouterEvenement('Don annulé par le donateur.');
        });

        $this->prevenir($don->association->user, $don);
    }

    /**
     * Envoie l'e-mail sans bloquer le parcours : si le serveur d'e-mails est indisponible,
     * l'erreur est journalisée et le don avance quand même.
     */
    private function prevenir(?User $destinataire, Don $don): void
    {
        try {
            $destinataire?->notify(new DonMisAJour($don));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

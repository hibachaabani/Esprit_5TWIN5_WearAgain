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
 *
 * Chaque étape vérifie, sous verrou, que le don en est encore là : un double clic ou deux onglets
 * ne le font pas avancer deux fois (ni n'envoient deux e-mails). Elle renvoie false sinon.
 */
class WorkflowDon
{
    public function proposer(Don $don): void
    {
        $don->ajouterEvenement('Don proposé'.($don->besoin ? " pour le besoin « {$don->besoin->titre} »" : '').'.');
        $this->prevenir($don->association->user, $don);
    }

    /** @param  array{date_remise: string, lieu_remise: string, consigne?: ?string}  $remise */
    public function accepter(Don $don, array $remise): bool
    {
        $fait = $this->avancer($don, ['propose'], function () use ($don, $remise) {
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

        if ($fait) {
            $this->prevenir($don->donateur, $don);
        }

        return $fait;
    }

    public function refuser(Don $don, string $motif): bool
    {
        $fait = $this->avancer($don, ['propose'], function () use ($don, $motif) {
            $don->update(['statut' => 'refuse', 'motif_refus' => $motif, 'cloture_le' => now()]);
            $don->ajouterEvenement("Don refusé. Motif : {$motif}");
        });

        if ($fait) {
            $this->prevenir($don->donateur, $don);
        }

        return $fait;
    }

    public function remettre(Don $don): bool
    {
        $fait = $this->avancer($don, ['accepte'], function () use ($don) {
            $don->update(['statut' => 'remis', 'date_don' => now()->toDateString(), 'cloture_le' => now()]);
            $don->ajouterEvenement('Vêtement remis à l\'association. Merci !');
            $don->besoin?->verifierObjectif();
        });

        if ($fait) {
            $this->prevenir($don->donateur, $don);
        }

        return $fait;
    }

    public function annuler(Don $don): bool
    {
        $fait = $this->avancer($don, ['propose', 'accepte'], function () use ($don) {
            $don->update(['statut' => 'annule', 'cloture_le' => now()]);
            $don->ajouterEvenement('Don annulé par le donateur.');
        });

        if ($fait) {
            $this->prevenir($don->association->user, $don);
        }

        return $fait;
    }

    /**
     * Applique le changement seulement si le don est encore à l'une des étapes attendues.
     * Le verrou fait attendre une seconde demande simultanée, qui trouve alors le statut déjà changé.
     */
    private function avancer(Don $don, array $depuis, \Closure $changement): bool
    {
        return DB::transaction(function () use ($don, $depuis, $changement) {
            $statutActuel = Don::whereKey($don->getKey())->lockForUpdate()->value('statut');

            if (! in_array($statutActuel, $depuis, true)) {
                $don->setRawAttributes(['statut' => $statutActuel] + $don->getAttributes(), true);

                return false;
            }

            $changement();

            return true;
        });
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

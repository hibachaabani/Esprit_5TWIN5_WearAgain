<?php

namespace Database\Seeders;

use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use App\Notifications\DonMisAJour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

/**
 * Scénario de démonstration entre donateur@wearagain.tn et « Les Mains Solidaires » :
 * un don accepté avec rendez-vous et discussion, un don à traiter, un don refusé avec motif.
 */
class ScenarioDonSeeder extends Seeder
{
    public function run(): void
    {
        $association = Association::where('numero_agrement', 'AGR-2026-0042')->first();
        $donateur = User::where('email', 'donateur@wearagain.tn')->first();

        if (! $association || ! $donateur) {
            return;
        }

        $this->donsPourLeTriIA($association, $donateur);

        if (Don::where('vetement_titre', 'Doudoune 8 ans')->where('statut', 'accepte')->exists()) {
            return;
        }

        $besoin = $association->besoins()->where('titre', "Manteaux d'hiver pour enfants")->first();
        $point = $association->pointsCollecte()->orderBy('id')->first();
        $lieu = $point ? "{$point->nom}, {$point->adresse}, {$point->ville}" : $association->adresse;

        // 1. Accepté : rendez-vous fixé et échange de messages.
        $accepte = Don::create([
            'vetement_titre' => 'Doudoune 8 ans',
            'donateur_id' => $donateur->id,
            'association_id' => $association->id,
            'besoin_id' => $besoin?->id,
            'mode' => 'depot',
            'statut' => 'accepte',
            'message' => 'Doudoune bleue portée un hiver, très bon état.',
            'date_remise' => now()->addDays(4)->setTime(10, 30),
            'lieu_remise' => $lieu,
            'consigne' => 'Vêtement propre, dans un sac fermé. Sonnez à l\'accueil.',
            'accepte_le' => now(),
        ]);
        $accepte->ajouterEvenement('Don proposé'.($besoin ? " pour le besoin « {$besoin->titre} »" : '').'.');
        $accepte->ajouterEvenement('Don accepté. Remise prévue le '.$accepte->date_remise->format('d/m/Y à H:i')." : {$lieu}.");
        $accepte->messages()->create(['user_id' => $donateur->id, 'type' => 'message', 'contenu' => 'Bonjour, je peux aussi passer en fin d\'après-midi si c\'est plus simple.', 'lu_le' => now()]);
        $accepte->messages()->create(['user_id' => $association->user_id, 'type' => 'message', 'contenu' => 'Pas de souci, l\'accueil est ouvert jusqu\'à 18h. Merci beaucoup !']);

        // 2. À traiter : l'association doit répondre.
        $propose = Don::create([
            'vetement_titre' => 'Parka 10 ans',
            'donateur_id' => $donateur->id,
            'association_id' => $association->id,
            'besoin_id' => $besoin?->id,
            'mode' => 'livraison',
            'adresse_retrait' => '5 rue de Marseille, Sousse',
            'statut' => 'propose',
            'message' => 'Je ne peux pas me déplacer en semaine.',
        ]);
        $propose->ajouterEvenement('Don proposé'.($besoin ? " pour le besoin « {$besoin->titre} »" : '').'.');

        // 3. Refusé avec motif.
        $refuse = Don::create([
            'vetement_titre' => 'Costume homme',
            'donateur_id' => $donateur->id,
            'association_id' => $association->id,
            'mode' => 'depot',
            'statut' => 'refuse',
            'motif_refus' => 'Nous ne collectons que des vêtements pour enfants et bébés.',
            'cloture_le' => now()->subDays(2),
        ]);
        $refuse->ajouterEvenement('Don proposé.');
        $refuse->ajouterEvenement("Don refusé. Motif : {$refuse->motif_refus}");

        // La cloche de chacun, sans envoyer d'e-mail : don à traiter pour l'association, réponses pour le donateur.
        Notification::sendNow($association->user, new DonMisAJour($propose), ['database']);
        Notification::sendNow($donateur, new DonMisAJour($refuse), ['database']);
        Notification::sendNow($donateur, new DonMisAJour($accepte), ['database']);
    }

    /** Deux dons à traiter de plus, pour montrer le tri intelligent : l'un répond à un besoin, l'autre non. */
    private function donsPourLeTriIA(Association $association, User $donateur): void
    {
        foreach ([
            ['Body 3 mois en coton', 'Lot de 3 bodies, lavés et repassés.'],
            ['Costume homme taille 52', 'Porté deux fois.'],
        ] as [$vetement, $message]) {
            if (Don::where('association_id', $association->id)->where('vetement_titre', $vetement)->exists()) {
                continue;
            }

            Don::create([
                'vetement_titre' => $vetement,
                'donateur_id' => $donateur->id,
                'association_id' => $association->id,
                'mode' => 'depot',
                'statut' => 'propose',
                'message' => $message,
            ])->ajouterEvenement('Don proposé.');
        }
    }
}

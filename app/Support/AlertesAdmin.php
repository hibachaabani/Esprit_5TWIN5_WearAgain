<?php

namespace App\Support;

use App\Models\Association;
use App\Models\Avis;
use App\Models\Besoin;
use App\Models\Depot;
use App\Models\Don;
use App\Models\Reparation;
use App\Models\Signalement;
use App\Models\Transformation;
use App\Models\Vetement;

/**
 * Ce qui attend une action de l'administrateur, module par module.
 * Sert au menu du back-office (pastilles) et à la liste « À traiter » du tableau de bord.
 * Calculé une seule fois par requête (helper once() de Laravel).
 */
class AlertesAdmin
{
    /**
     * @return array<string, array{nombre: int, libelle: string, detail: string, route: string, parametres: array, niveau: string, icone: string}>
     */
    public static function toutes(): array
    {
        return once(fn () => [
            'associations' => self::alerte(Association::where('verifiee', false)->count(), 'association(s) à vérifier', 'Vérifiez leur numéro d\'agrément pour qu\'elles reçoivent des dons.', 'admin.associations.index', [], 'haute', 'associations'),
            'vetements' => self::alerte(Vetement::where('signale', true)->count(), 'annonce(s) signalée(s)', 'Masquez ou supprimez les annonces qui posent problème.', 'admin.vetements.index', [], 'haute', 'vetements'),
            'signalements' => self::alerte(Signalement::ouverts()->where('priorite', 'haute')->count(), 'signalement(s) urgent(s)', 'Points de collecte pleins ou inaccessibles, pas encore traités.', 'admin.signalements.index', ['statut' => 'ouvert', 'priorite' => 'haute'], 'haute', 'signalements'),
            'avis' => self::alerte(Avis::where('ia_risque_niveau', 'eleve')->count(), 'avis suspect(s)', 'Avis classés à risque élevé par l\'analyse IA.', 'admin.avis.index', [], 'moyenne', 'avis'),
            'depots' => self::alerte(Depot::enRetard()->count(), 'dépôt(s) en retard', 'Déclarés mais jamais apportés : annulés automatiquement après 7 jours.', 'admin.depots.index', ['retard' => 1], 'moyenne', 'depots'),
            'besoins' => self::alerte(Besoin::ouverts()->where('urgence', 'urgente')->count(), 'besoin(s) urgent(s)', 'Besoins des associations à mettre en avant.', 'admin.besoins.index', [], 'moyenne', 'besoins'),
            'dons' => self::alerte(Don::where('statut', 'propose')->count(), 'don(s) en attente de réponse', 'Proposés par des particuliers, pas encore acceptés par l\'association.', 'admin.dons.index', [], 'basse', 'dons'),
            'reparations' => self::alerte(Reparation::where('statut', 'en_attente')->count(), 'réparation(s) sans devis', 'Demandes que les ateliers n\'ont pas encore chiffrées.', 'admin.reparations.index', [], 'basse', 'reparations'),
            'transformations' => self::alerte(Transformation::where('statut', 'en_attente')->count(), 'transformation(s) sans devis', 'Demandes que les ateliers n\'ont pas encore chiffrées.', 'admin.transformations.index', [], 'basse', 'transformations'),
        ]);
    }

    /** Nombre à afficher dans le menu pour un module (0 = pas de pastille). */
    public static function nombre(string $cle): int
    {
        return self::toutes()[$cle]['nombre'] ?? 0;
    }

    /**
     * Uniquement ce qui demande une action, du plus urgent au moins urgent.
     *
     * @return list<array{nombre: int, libelle: string, detail: string, route: string, parametres: array, niveau: string, icone: string}>
     */
    public static function aTraiter(): array
    {
        $ordre = ['haute' => 0, 'moyenne' => 1, 'basse' => 2];

        return collect(self::toutes())
            ->filter(fn (array $alerte) => $alerte['nombre'] > 0)
            ->sortBy(fn (array $alerte) => $ordre[$alerte['niveau']])
            ->values()
            ->all();
    }

    private static function alerte(int $nombre, string $libelle, string $detail, string $route, array $parametres, string $niveau, string $icone): array
    {
        return compact('nombre', 'libelle', 'detail', 'route', 'parametres', 'niveau', 'icone');
    }
}

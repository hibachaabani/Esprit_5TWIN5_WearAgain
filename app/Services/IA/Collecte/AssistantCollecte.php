<?php

namespace App\Services\IA\Collecte;

use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\Signalement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Assistant IA du module collecte.
 *
 * Chaque fonction interroge Groq, puis vérifie la réponse (valeurs autorisées, points
 * existants). Si Groq n'est pas configuré, ne répond pas ou répond mal, un moteur local
 * à base de règles donne un résultat équivalent. Le champ « source » indique d'où vient
 * le résultat : « ia » (Groq) ou « local ».
 */
class AssistantCollecte
{
    private const PRIORITES = ['haute', 'moyenne', 'basse'];

    public function __construct(private ClientGroq $groq) {}

    /* ----------------------------------------------------------------------
     | 1. « Où déposer ? » : recommander des points à partir d'une question libre
     * -------------------------------------------------------------------- */

    /**
     * @return array{reponse: string, conseil: string, points: list<array<string, mixed>>, source: string}
     */
    public function ouDeposer(string $question, ?float $latitude = null, ?float $longitude = null): array
    {
        $candidats = $this->pointsOuverts($latitude, $longitude);

        if ($candidats->isEmpty()) {
            return [
                'reponse' => 'Aucun point de collecte n\'a de place libre pour le moment. Réessaie dans quelques jours.',
                'conseil' => '',
                'points' => [],
                'source' => 'local',
            ];
        }

        try {
            $donnees = $this->groq->json(
                <<<'PROMPT'
                Tu es l'assistant de WearAgain, une plateforme tunisienne de collecte de vêtements.
                On te donne une question d'un particulier et la liste JSON des points de collecte ouverts qui ont de la place.
                Choisis au plus 3 points adaptés (ville ou quartier cité, distance si elle est donnée, places libres, horaires).
                Réponds UNIQUEMENT avec ce JSON :
                {"reponse": "2 ou 3 phrases en français, tutoiement, qui expliquent ton choix",
                 "points": [identifiants des points choisis, du meilleur au moins bon],
                 "conseil": "une phrase pour bien préparer les vêtements"}
                N'invente aucun point : utilise seulement les identifiants fournis.
                PROMPT,
                "Question : {$question}\n\nPoints disponibles :\n".json_encode($candidats->values()->all(), JSON_UNESCAPED_UNICODE),
            );

            $ids = collect($donnees['points'] ?? [])->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $candidats->has($id))->unique()->take(3)->values();

            if ($ids->isEmpty() || ! is_string($donnees['reponse'] ?? null)) {
                throw new \RuntimeException('Réponse Groq sans point valable.');
            }

            return [
                'reponse' => Str::limit(trim($donnees['reponse']), 600),
                'conseil' => Str::limit(trim((string) ($donnees['conseil'] ?? '')), 200),
                'points' => $ids->map(fn (int $id) => $candidats[$id])->all(),
                'source' => 'ia',
            ];
        } catch (Throwable $e) {
            $this->journaliser('ouDeposer', $e);

            return $this->ouDeposerLocal($question, $candidats, $latitude !== null);
        }
    }

    /**
     * Points ouverts et non pleins, indexés par identifiant (au plus 15, les plus proches d'abord).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function pointsOuverts(?float $latitude, ?float $longitude): Collection
    {
        $points = PointCollecte::actifs()->withCount('depotsActifs')->with('association:id,nom')->get()
            ->reject(fn (PointCollecte $p) => $p->estComplet());

        if ($latitude !== null && $longitude !== null) {
            $points = $points->sortBy(fn (PointCollecte $p) => $p->distanceDe($latitude, $longitude));
        } else {
            $points = $points->sortByDesc(fn (PointCollecte $p) => $p->placesRestantes());
        }

        return $points->take(15)->mapWithKeys(fn (PointCollecte $p) => [$p->id => array_filter([
            'id' => $p->id,
            'nom' => $p->nom,
            'adresse' => $p->adresse,
            'ville' => $p->ville,
            'horaires' => $p->horaires,
            'places_libres' => $p->placesRestantes(),
            'association' => $p->association?->nom,
            'distance_km' => $latitude !== null && $longitude !== null ? round($p->distanceDe($latitude, $longitude), 1) : null,
            'url' => route('points-collecte.show', $p),
        ], fn ($valeur) => $valeur !== null)]);
    }

    private function ouDeposerLocal(string $question, Collection $candidats, bool $avecPosition): array
    {
        $texte = Str::of($question)->ascii()->lower();
        $villeCitee = $candidats->filter(fn (array $p) => $texte->contains(Str::of($p['ville'])->ascii()->lower()->value()));
        $choisis = ($villeCitee->isNotEmpty() ? $villeCitee : $candidats)->take(3)->values();

        $raison = match (true) {
            $villeCitee->isNotEmpty() => 'dans la ville que tu as citée',
            $avecPosition => 'les plus proches de ta position',
            default => 'ceux qui ont le plus de place libre',
        };

        return [
            'reponse' => "Voici les points ouverts {$raison}. Clique sur un point pour voir ses horaires et l'itinéraire.",
            'conseil' => 'Lave et plie les vêtements, puis dépose-les dans un sac fermé.',
            'points' => $choisis->all(),
            'source' => 'local',
        ];
    }

    /* ----------------------------------------------------------------------
     | 2. Signalements : priorité, résumé et proposition de réponse
     * -------------------------------------------------------------------- */

    /**
     * @return array{priorite: string, resume: string, source: string}
     */
    public function analyserSignalement(Signalement $signalement): array
    {
        try {
            $donnees = $this->groq->json(
                <<<'PROMPT'
                Tu aides une association tunisienne à gérer ses points de collecte de vêtements.
                Analyse le signalement d'un particulier et réponds UNIQUEMENT avec ce JSON :
                {"priorite": "haute" | "moyenne" | "basse", "resume": "résumé en 15 mots maximum"}
                Haute : point inutilisable, débordement, danger ou blocage. Moyenne : gêne réelle mais le point fonctionne. Basse : remarque ou information.
                PROMPT,
                "Point : {$signalement->pointCollecte->nom}\nType : {$signalement->libelleType()}\nMessage : {$signalement->message}",
                0.1,
            );

            $priorite = Str::lower((string) ($donnees['priorite'] ?? ''));

            if (! in_array($priorite, self::PRIORITES, true) || blank($donnees['resume'] ?? null)) {
                throw new \RuntimeException('Priorité ou résumé invalide.');
            }

            return ['priorite' => $priorite, 'resume' => Str::limit(trim($donnees['resume']), 150), 'source' => 'ia'];
        } catch (Throwable $e) {
            $this->journaliser('analyserSignalement', $e);

            return $this->analyserSignalementLocal($signalement);
        }
    }

    private function analyserSignalementLocal(Signalement $signalement): array
    {
        $message = Str::of($signalement->message)->ascii()->lower();
        $urgent = $message->contains(['urgent', 'danger', 'feu', 'bloque', 'deborde', 'vol', 'casse']);

        $priorite = match (true) {
            $urgent, in_array($signalement->type, ['plein', 'inaccessible'], true) => 'haute',
            in_array($signalement->type, ['endommage', 'sale'], true) => 'moyenne',
            default => 'basse',
        };

        return [
            'priorite' => $priorite,
            'resume' => $signalement->libelleType().' : '.Str::limit($signalement->message, 90),
            'source' => 'local',
        ];
    }

    /**
     * @return array{reponse: string, source: string}
     */
    public function proposerReponse(Signalement $signalement): array
    {
        $association = $signalement->pointCollecte->association?->nom ?? 'L\'association';

        try {
            $donnees = $this->groq->json(
                <<<'PROMPT'
                Tu rédiges, au nom d'une association de collecte de vêtements, une réponse courte (60 mots maximum),
                polie et concrète à un particulier qui a signalé un problème sur un point de collecte.
                Remercie-le, dis ce que l'association va faire, vouvoie-le, ne promets pas de date précise.
                Réponds UNIQUEMENT avec ce JSON : {"reponse": "..."}
                PROMPT,
                "Association : {$association}\nPoint : {$signalement->pointCollecte->nom}\nType : {$signalement->libelleType()}\nMessage : {$signalement->message}",
                0.5,
            );

            if (blank($donnees['reponse'] ?? null)) {
                throw new \RuntimeException('Réponse vide.');
            }

            return ['reponse' => Str::limit(trim($donnees['reponse']), 600), 'source' => 'ia'];
        } catch (Throwable $e) {
            $this->journaliser('proposerReponse', $e);

            $action = match ($signalement->type) {
                'plein' => 'Nous programmons le vidage du conteneur lors de notre prochaine tournée.',
                'endommage' => 'Nous allons faire réparer le conteneur rapidement.',
                'sale' => 'Nous allons nettoyer les abords du point.',
                'inaccessible' => 'Nous faisons le nécessaire pour libérer l\'accès au point.',
                default => 'Nous étudions votre remarque et revenons vers vous si besoin.',
            };

            return ['reponse' => "Merci pour votre signalement. {$action} — {$association}", 'source' => 'local'];
        }
    }

    /* ----------------------------------------------------------------------
     | 3. Campagnes : rédiger la description
     * -------------------------------------------------------------------- */

    /**
     * @param  array{titre: string, objectif?: int|null, point?: string|null, date_debut?: string|null, date_fin?: string|null}  $campagne
     * @return array{description: string, source: string}
     */
    public function redigerCampagne(array $campagne, string $association): array
    {
        $titre = $campagne['titre'];
        $objectif = $campagne['objectif'] ?? null;
        $lieu = $campagne['point'] ?? 'tous nos points de collecte';

        try {
            $donnees = $this->groq->json(
                <<<'PROMPT'
                Tu rédiges la description d'une campagne de collecte de vêtements d'une association tunisienne.
                80 à 120 mots en français, ton chaleureux et concret : commence par une phrase d'accroche,
                dis ce qu'on peut apporter, où et jusqu'à quand, et à qui cela profite. Pas d'emoji, pas de hashtag.
                Réponds UNIQUEMENT avec ce JSON : {"description": "..."}
                PROMPT,
                "Association : {$association}\nTitre : {$titre}\nObjectif : ".($objectif ?: 'non précisé')." vêtements\nLieu : {$lieu}\nDu {$this->date($campagne['date_debut'] ?? null)} au {$this->date($campagne['date_fin'] ?? null)}",
                0.7,
            );

            if (blank($donnees['description'] ?? null)) {
                throw new \RuntimeException('Description vide.');
            }

            return ['description' => Str::limit(trim($donnees['description']), 1800), 'source' => 'ia'];
        } catch (Throwable $e) {
            $this->journaliser('redigerCampagne', $e);

            $objectifTexte = $objectif ? " Notre objectif : {$objectif} vêtements." : '';

            return [
                'description' => "{$titre} : donnez une seconde vie à vos vêtements !{$objectifTexte} "
                    ."Apportez vos vêtements propres et en bon état à {$lieu}, du {$this->date($campagne['date_debut'] ?? null)} "
                    ."au {$this->date($campagne['date_fin'] ?? null)}. {$association} les trie puis les redistribue "
                    .'aux familles qui en ont besoin. Merci pour votre générosité.',
                'source' => 'local',
            ];
        }
    }

    private function date(?string $date): string
    {
        return $date ? Carbon::parse($date)->translatedFormat('j F Y') : '…';
    }

    /* ----------------------------------------------------------------------
     | 4. Conseils pour l'association : quoi faire en priorité aujourd'hui
     * -------------------------------------------------------------------- */

    /**
     * @return array{resume: string, actions: list<array{priorite: string, action: string}>, source: string}
     */
    public function conseilsCollecte(Association $association): array
    {
        $faits = $this->faitsCollecte($association);
        $regles = $this->actionsLocales($faits);

        try {
            $donnees = $this->groq->json(
                <<<'PROMPT'
                Tu conseilles une association tunisienne qui gère des points de collecte de vêtements.
                On te donne la situation (JSON) et une première liste d'actions calculée par des règles.
                Classe et reformule les actions les plus utiles aujourd'hui (5 au maximum), concrètes, avec le nom des points.
                Réponds UNIQUEMENT avec ce JSON :
                {"resume": "2 phrases sur la situation", "actions": [{"priorite": "haute" | "moyenne" | "basse", "action": "une phrase"}]}
                N'invente aucun chiffre ni aucun point.
                PROMPT,
                "Situation :\n".json_encode($faits, JSON_UNESCAPED_UNICODE)."\n\nActions proposées par les règles :\n".json_encode($regles, JSON_UNESCAPED_UNICODE),
                0.3,
            );

            $actions = collect($donnees['actions'] ?? [])
                ->filter(fn ($a) => is_array($a) && in_array($a['priorite'] ?? null, self::PRIORITES, true) && filled($a['action'] ?? null))
                ->map(fn (array $a) => ['priorite' => $a['priorite'], 'action' => Str::limit(trim($a['action']), 250)])
                ->take(5)->values();

            if ($actions->isEmpty() || blank($donnees['resume'] ?? null)) {
                throw new \RuntimeException('Conseils vides.');
            }

            return ['resume' => Str::limit(trim($donnees['resume']), 400), 'actions' => $actions->all(), 'source' => 'ia'];
        } catch (Throwable $e) {
            $this->journaliser('conseilsCollecte', $e);

            return [
                'resume' => "{$faits['points_ouverts']} point(s) ouvert(s), {$faits['depots_a_recevoir']} dépôt(s) à recevoir et {$faits['signalements_ouverts']} signalement(s) en cours.",
                'actions' => $regles,
                'source' => 'local',
            ];
        }
    }

    /** Chiffres de la situation de l'association, partagés par les règles et par l'IA. */
    private function faitsCollecte(Association $association): array
    {
        $seuil = (int) config('collecte.seuil_alerte', 80);
        $points = PointCollecte::where('association_id', $association->id)
            ->withCount(['depotsActifs', 'signalements as signalements_ouverts_count' => fn ($q) => $q->ouverts()])
            ->get();
        $ids = $points->pluck('id');

        return [
            'association' => $association->nom,
            'points_ouverts' => $points->where('actif', true)->count(),
            'points_a_vider' => $points->filter(fn (PointCollecte $p) => $p->actif && $p->tauxRemplissage() >= $seuil)
                ->map(fn (PointCollecte $p) => ['point' => $p->nom, 'remplissage_pourcent' => $p->tauxRemplissage()])->values()->all(),
            'depots_a_recevoir' => Depot::whereIn('point_collecte_id', $ids)->aCollecter()->count(),
            'depots_en_retard' => Depot::whereIn('point_collecte_id', $ids)->enRetard()->count(),
            'depots_a_trier' => Depot::whereIn('point_collecte_id', $ids)->where('statut', 'recu')->count(),
            'signalements_ouverts' => (int) $points->sum('signalements_ouverts_count'),
            'signalements_urgents' => Signalement::whereIn('point_collecte_id', $ids)->ouverts()->where('priorite', 'haute')
                ->with('pointCollecte:id,nom')->get()
                ->map(fn (Signalement $s) => ['point' => $s->pointCollecte->nom, 'type' => $s->libelleType()])->all(),
            'campagnes_bientot_finies' => CampagneCollecte::where('association_id', $association->id)->enCours()
                ->whereDate('date_fin', '<=', today()->addDays((int) config('collecte.campagne_fin_proche_jours', 3)))->get()
                ->map(fn (CampagneCollecte $c) => ['campagne' => $c->titre, 'progression_pourcent' => $c->progression(), 'jours_restants' => $c->joursRestants()])
                ->all(),
        ];
    }

    /** @return list<array{priorite: string, action: string}> */
    private function actionsLocales(array $faits): array
    {
        $actions = [];

        foreach ($faits['signalements_urgents'] as $s) {
            $actions[] = ['priorite' => 'haute', 'action' => "Traiter le signalement « {$s['type']} » au point {$s['point']}."];
        }
        foreach ($faits['points_a_vider'] as $p) {
            $actions[] = ['priorite' => 'haute', 'action' => "Vider {$p['point']} : rempli à {$p['remplissage_pourcent']} %."];
        }
        if ($faits['depots_a_recevoir'] > 0) {
            $actions[] = ['priorite' => 'moyenne', 'action' => "Réceptionner {$faits['depots_a_recevoir']} dépôt(s) déjà apportés : planifie une tournée."];
        }
        if ($faits['depots_a_trier'] > 0) {
            $actions[] = ['priorite' => 'moyenne', 'action' => "Trier {$faits['depots_a_trier']} vêtement(s) reçu(s)."];
        }
        if ($faits['depots_en_retard'] > 0) {
            $actions[] = ['priorite' => 'basse', 'action' => "{$faits['depots_en_retard']} dépôt(s) en retard seront annulés automatiquement après ".config('collecte.expiration_jours').' jours.'];
        }
        foreach ($faits['campagnes_bientot_finies'] as $c) {
            $actions[] = ['priorite' => 'moyenne', 'action' => "Relancer la campagne « {$c['campagne']} » : {$c['progression_pourcent']} % de l'objectif, fin dans {$c['jours_restants']} jour(s)."];
        }

        return array_slice($actions, 0, 5) ?: [['priorite' => 'basse', 'action' => 'Tout est à jour : aucun point à vider ni signalement en attente.']];
    }

    private function journaliser(string $fonction, Throwable $e): void
    {
        if ($this->groq->estConfigure()) {
            Log::warning("Assistant collecte ({$fonction}) : moteur local utilisé, Groq indisponible.", ['erreur' => $e->getMessage()]);
        }
    }
}

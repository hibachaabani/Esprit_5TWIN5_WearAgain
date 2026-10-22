<?php

namespace App\Services\IA\Agent;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Services\IA\Besoins\RedactionBesoinIA;
use App\Services\IA\Dons\LexiqueVetements;
use App\Services\IA\Dons\MatchingBesoinsIA;
use App\Services\IA\Dons\TriDonsIA;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Les outils de l'agent association : ce qu'il a le droit de consulter ou de préparer.
 *
 * Chaque outil travaille uniquement sur l'association connectée et ne modifie rien :
 * il renvoie des données (lues par le modèle de langage) et des éléments (affichés à l'écran :
 * cartes de dons, de besoins, brouillon). Publier ou accepter reste un clic de l'association.
 */
class OutilsAssociation
{
    public const LIBELLES = [
        'resume_activite' => 'votre activité',
        'dons_a_traiter' => 'vos dons à traiter',
        'etat_des_besoins' => 'vos besoins',
        'dons_pour_un_besoin' => 'les dons pour un besoin',
        'rediger_besoin' => 'la rédaction d\'un besoin',
    ];

    private const CONSEILS = ['accepter' => 'Conseillé : accepter', 'verifier' => 'À vérifier', 'complet' => 'Besoin déjà complet', 'hors' => 'Hors de vos besoins'];

    public function __construct(
        private readonly TriDonsIA $tri,
        private readonly MatchingBesoinsIA $matching,
        private readonly RedactionBesoinIA $redaction,
    ) {}

    /** Description des outils au format « tools » de l'API Chat Completions. */
    public function definitions(): array
    {
        $outil = fn (string $nom, string $description, array $proprietes = [], array $requis = []) => [
            'type' => 'function',
            'function' => [
                'name' => $nom,
                'description' => $description,
                'parameters' => ['type' => 'object', 'properties' => (object) $proprietes, 'required' => $requis],
            ],
        ];

        return [
            $outil('resume_activite', "Vue d'ensemble de l'association : dons à traiter, remises prévues, besoins en cours, messages non lus. À utiliser pour « qu'est-ce que je dois faire ? »."),
            $outil('dons_a_traiter', "Dons proposés en attente de réponse, classés du plus utile au moins utile avec le conseil de l'IA (score sur 100 et raisons).",
                ['limite' => ['type' => 'integer', 'description' => 'Nombre de dons à renvoyer (1 à 10, 5 par défaut).']]),
            $outil('etat_des_besoins', 'Besoins publiés par l\'association avec leur progression : reçus, en route, restants, urgence, date limite, statut.'),
            $outil('dons_pour_un_besoin', 'Dons proposés qui correspondent le mieux à un besoin précis de l\'association.',
                ['besoin' => ['type' => 'string', 'description' => 'Identifiant du besoin, ou mots de son titre (ex : « manteaux enfants »).']], ['besoin']),
            $outil('rediger_besoin', 'Prépare un BROUILLON de besoin (titre, description, catégorie, quantité, urgence, date limite) à partir de la demande. Rien n\'est publié : l\'association relit et publie elle-même.',
                ['demande' => ['type' => 'string', 'description' => 'La demande de l\'association, avec ses propres mots.']], ['demande']),
        ];
    }

    /**
     * @return array{donnees: array, elements: array<int, array>}
     */
    public function executer(string $nom, array $arguments, Association $association): array
    {
        return match ($nom) {
            'resume_activite' => $this->resumeActivite($association),
            'dons_a_traiter' => $this->donsATraiter($association, (int) ($arguments['limite'] ?? 5)),
            'etat_des_besoins' => $this->etatDesBesoins($association),
            'dons_pour_un_besoin' => $this->donsPourUnBesoin($association, (string) ($arguments['besoin'] ?? '')),
            'rediger_besoin' => $this->redigerBesoin($association, (string) ($arguments['demande'] ?? '')),
            default => ['donnees' => ['erreur' => "Outil inconnu : {$nom}."], 'elements' => []],
        };
    }

    private function resumeActivite(Association $association): array
    {
        $compteurs = $association->dons()->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $remises = $association->dons()->with('donateur')->where('statut', 'accepte')
            ->orderByRaw('date_remise is null')->orderBy('date_remise')->limit(5)->get();
        $besoins = $this->besoinsEnCours($association);

        return [
            'donnees' => [
                'association' => $association->nom,
                'verifiee' => $association->peutRecevoirDesDons(),
                'dons_a_traiter' => (int) ($compteurs['propose'] ?? 0),
                'remises_prevues' => $remises->map(fn (Don $don) => [
                    'vetement' => $don->vetement_titre,
                    'donateur' => $this->prenom($don),
                    'date' => $don->date_remise?->format('d/m/Y à H\hi'),
                    'lieu' => $don->lieu_remise,
                ])->all(),
                'dons_remis_ce_mois' => $association->dons()->where('statut', 'remis')->where('cloture_le', '>=', now()->startOfMonth())->count(),
                'besoins_en_cours' => $besoins->count(),
                'besoins_urgents' => $besoins->where('urgence', 'urgente')->pluck('titre')->values()->all(),
                'messages_non_lus' => $association->user?->messagesNonLus() ?? 0,
            ],
            'elements' => $remises->map(fn (Don $don) => $this->carteDon($don, null))->all(),
        ];
    }

    private function donsATraiter(Association $association, int $limite): array
    {
        $dons = $association->dons()->with(['donateur', 'besoin'])->where('statut', 'propose')->get();
        $tries = $this->tri->trier($dons, $association)->take(max(1, min(10, $limite)));

        return [
            'donnees' => [
                'total_a_traiter' => $dons->count(),
                'dons' => $tries->map(fn (Don $don) => [
                    'id' => $don->id,
                    'vetement' => $don->vetement_titre,
                    'donateur' => $this->prenom($don),
                    'propose_le' => $don->created_at->format('d/m/Y'),
                    'besoin_vise' => $don->besoin?->titre,
                    'score' => $don->avis_ia['score'],
                    'conseil' => self::CONSEILS[$don->avis_ia['conseil']],
                    'raisons' => $don->avis_ia['raisons'],
                ])->all(),
            ],
            'elements' => $tries->map(fn (Don $don) => $this->carteDon($don, $don->avis_ia))->all(),
        ];
    }

    private function etatDesBesoins(Association $association): array
    {
        $besoins = $association->besoins()->avecProgression()->parPriorite()->limit(15)->get();

        return [
            'donnees' => ['besoins' => $besoins->map(fn (Besoin $besoin) => [
                'id' => $besoin->id,
                'titre' => $besoin->titre,
                'statut' => $besoin->libelleStatut(),
                'urgence' => Besoin::URGENCES[$besoin->urgence] ?? $besoin->urgence,
                'quantite' => $besoin->quantite,
                'recus' => $besoin->quantiteRecue(),
                'en_route' => $besoin->quantiteEnRoute(),
                'restants' => $besoin->quantiteRestante(),
                'progression' => $besoin->progression().' %',
                'date_limite' => $besoin->date_limite?->format('d/m/Y'),
            ])->all()],
            'elements' => $besoins->map(fn (Besoin $besoin) => $this->carteBesoin($besoin))->all(),
        ];
    }

    private function donsPourUnBesoin(Association $association, string $recherche): array
    {
        $besoin = $this->trouverBesoin($association, $recherche);

        if (! $besoin) {
            return [
                'donnees' => [
                    'erreur' => "Aucun besoin de l'association ne correspond à « {$recherche} ».",
                    'besoins_disponibles' => $this->besoinsEnCours($association)->pluck('titre')->all(),
                ],
                'elements' => [],
            ];
        }

        $correspondances = $association->dons()->with(['donateur', 'besoin'])->where('statut', 'propose')->get()
            ->map(function (Don $don) use ($besoin) {
                $note = $this->matching->noter($besoin, $this->tri->analyse($don));
                $don->setAttribute('avis_ia', [
                    'score' => $note['score'],
                    'conseil' => $note['pertinent'] ? ($note['score'] >= 60 ? 'accepter' : 'verifier') : 'hors',
                    'raisons' => array_values(array_filter($note['raisons'], fn ($r) => ! str_starts_with($r, 'Il en manque'))),
                ]);

                return $don;
            })
            ->filter(fn (Don $don) => $don->avis_ia['conseil'] !== 'hors')
            ->sortByDesc(fn (Don $don) => $don->avis_ia['score'])
            ->take(5)
            ->values();

        return [
            'donnees' => [
                'besoin' => ['id' => $besoin->id, 'titre' => $besoin->titre, 'restants' => $besoin->quantiteRestante(), 'quantite' => $besoin->quantite],
                'dons_correspondants' => $correspondances->map(fn (Don $don) => [
                    'id' => $don->id,
                    'vetement' => $don->vetement_titre,
                    'donateur' => $this->prenom($don),
                    'score' => $don->avis_ia['score'],
                    'raisons' => $don->avis_ia['raisons'],
                ])->all(),
            ],
            'elements' => [$this->carteBesoin($besoin), ...$correspondances->map(fn (Don $don) => $this->carteDon($don, $don->avis_ia))->all()],
        ];
    }

    private function redigerBesoin(Association $association, string $demande): array
    {
        $demande = trim(mb_substr($demande, 0, 500));

        if (mb_strlen($demande) < 5) {
            return ['donnees' => ['erreur' => 'La demande est trop courte pour rédiger un besoin.'], 'elements' => []];
        }

        $proposition = $this->redaction->rediger($demande, $association->nom)->toArray();

        return [
            'donnees' => ['brouillon' => $proposition, 'rappel' => "Brouillon seulement : l'association doit le relire et le publier elle-même."],
            'elements' => [['type' => 'brouillon'] + $proposition + [
                'categorie_libelle' => $proposition['categorie'] ? Association::CATEGORIES[$proposition['categorie']] : null,
                'urgence_libelle' => Besoin::URGENCES[$proposition['urgence']],
            ]],
        ];
    }

    /** Le besoin désigné par son identifiant ou par les mots de son titre (vêtements en commun, sinon mots en commun). */
    private function trouverBesoin(Association $association, string $recherche): ?Besoin
    {
        $besoins = $association->besoins()->avecProgression()->latest()->get();

        if (ctype_digit(trim($recherche))) {
            return $besoins->firstWhere('id', (int) trim($recherche));
        }

        $mots = LexiqueVetements::mots($recherche);
        $familles = LexiqueVetements::familles($mots);

        return $besoins
            ->map(function (Besoin $besoin) use ($mots, $familles) {
                $motsBesoin = LexiqueVetements::mots($besoin->titre);
                $score = 2 * count(array_intersect($familles, LexiqueVetements::familles($motsBesoin))) + count(array_intersect($mots, $motsBesoin));

                return ['besoin' => $besoin, 'score' => $score + ($besoin->cleStatut() === 'ouvert' ? 0.5 : 0)];
            })
            ->filter(fn ($resultat) => $resultat['score'] >= 1)
            ->sortByDesc('score')
            ->first()['besoin'] ?? null;
    }

    private function besoinsEnCours(Association $association): Collection
    {
        return $this->tri->besoinsEnCours($association);
    }

    /** Seulement le prénom du donateur : l'agent n'a pas besoin de plus. */
    private function prenom(Don $don): string
    {
        return Str::before(trim((string) $don->donateur?->name), ' ') ?: 'Un donateur';
    }

    private function carteDon(Don $don, ?array $avis): array
    {
        return [
            'type' => 'don',
            'id' => $don->id,
            'titre' => $don->vetement_titre,
            'detail' => $don->statut === 'accepte' && $don->date_remise
                ? $this->prenom($don).' · remise le '.$don->date_remise->format('d/m à H\hi').($don->lieu_remise ? ' · '.$don->lieu_remise : '')
                : $this->prenom($don).' · proposé le '.$don->created_at->format('d/m'),
            'conseil' => $avis['conseil'] ?? null,
            'conseil_libelle' => isset($avis['conseil']) ? self::CONSEILS[$avis['conseil']] : null,
            'score' => $avis['score'] ?? null,
            'url' => route('dons.show', $don),
        ];
    }

    private function carteBesoin(Besoin $besoin): array
    {
        return [
            'type' => 'besoin',
            'id' => $besoin->id,
            'titre' => $besoin->titre,
            'statut' => $besoin->libelleStatut(),
            'urgence' => $besoin->urgence,
            'quantite' => $besoin->quantite,
            'recus' => $besoin->quantiteRecue(),
            'en_route' => $besoin->quantiteEnRoute(),
            'restants' => $besoin->quantiteRestante(),
            'progression' => $besoin->progression(),
            'progression_en_route' => $besoin->progressionEnRoute(),
            'url' => route('besoins.show', $besoin),
        ];
    }
}

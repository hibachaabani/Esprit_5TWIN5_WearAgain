<?php

namespace App\Services\IA\Agent;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Services\IA\Besoins\RedactionBesoinIA;
use App\Services\IA\Dons\AnalyseurDonLocal;
use App\Services\IA\Dons\LexiqueVetements;
use App\Services\IA\Dons\MatchingBesoinsIA;
use App\Services\IA\Dons\TriDonsIA;
use Carbon\CarbonInterface;
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
        'statistiques_dons' => 'vos statistiques',
    ];

    private const CONSEILS = ['accepter' => 'Conseillé : accepter', 'verifier' => 'À vérifier', 'complet' => 'Besoin déjà complet', 'hors' => 'Hors de vos besoins'];

    public function __construct(
        private readonly TriDonsIA $tri,
        private readonly MatchingBesoinsIA $matching,
        private readonly RedactionBesoinIA $redaction,
        private readonly AnalyseurDonLocal $analyseurLocal,
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
            $outil('statistiques_dons', "Bilan chiffré des dons de l'association : dons proposés et remis par mois (6 derniers mois), totaux par statut, taux d'acceptation, délai moyen de réponse, donateurs les plus fidèles, catégories de vêtements les plus reçues. À utiliser pour « combien », « bilan », « statistiques »."),
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
            'statistiques_dons' => $this->statistiquesDons($association),
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

    /**
     * Bilan de l'activité, calculé ici et non par le modèle : les chiffres sont exacts.
     * Les catégories viennent du moteur local, sans appel à l'API pour chaque don.
     */
    private function statistiquesDons(Association $association): array
    {
        $dons = $association->dons()->with('donateur')->get();
        $statuts = $dons->countBy('statut');
        $recus = $dons->where('statut', '!=', 'annule');

        $parMois = collect(range(5, 0))->map(function (int $ecart) use ($dons) {
            $mois = now()->startOfMonth()->subMonthsNoOverflow($ecart);

            return [
                'mois' => Str::ucfirst($mois->locale('fr')->isoFormat('MMM YYYY')),
                'proposes' => $dons->filter(fn (Don $don) => $don->created_at->isSameMonth($mois))->count(),
                'remis' => $dons->filter(fn (Don $don) => $don->statut === 'remis' && $don->cloture_le?->isSameMonth($mois))->count(),
            ];
        })->values();

        // Taux d'acceptation : parmi les dons auxquels l'association a répondu.
        $acceptes = ($statuts['accepte'] ?? 0) + ($statuts['remis'] ?? 0);
        $reponses = $acceptes + ($statuts['refuse'] ?? 0);

        // Délai de réponse : de la proposition à l'acceptation ou au refus.
        $delais = $dons->map(fn (Don $don) => $this->dateDeReponse($don)?->diffInMinutes($don->created_at, true))
            ->filter(fn ($minutes) => $minutes !== null);

        $fideles = $recus->groupBy('donateur_id')
            ->map(fn (Collection $sesDons) => [
                'prenom' => $this->prenom($sesDons->first()),
                'dons' => $sesDons->count(),
                'remis' => $sesDons->where('statut', 'remis')->count(),
            ])
            ->sortByDesc('dons')->take(3)->values();

        $categories = $recus
            ->map(fn (Don $don) => $this->analyseurLocal->analyser($don->vetement_titre)['categorie'])
            ->map(fn (?string $cle) => $cle ? Association::CATEGORIES[$cle] : 'Autres')
            ->countBy()->sortDesc()->take(4);

        $ceMois = $parMois->last();
        $donnees = [
            'ce_mois' => [
                'nouveaux dons proposés ce mois, quel que soit leur statut actuel' => $ceMois['proposes'],
                'vêtements remis à l\'association ce mois' => $ceMois['remis'],
                'dons qui attendent encore une réponse (tous mois confondus)' => $statuts['propose'] ?? 0,
            ],
            'par_mois' => $parMois->all(),
            'total' => $dons->count(),
            // Libellés explicites : un code comme « accepte » se confond facilement avec « remis ».
            'par_statut' => [
                'proposés, en attente de réponse' => $statuts['propose'] ?? 0,
                'acceptés, remise encore à venir' => $statuts['accepte'] ?? 0,
                'remis, déjà reçus par l\'association' => $statuts['remis'] ?? 0,
                'refusés' => $statuts['refuse'] ?? 0,
                'annulés par le donateur' => $statuts['annule'] ?? 0,
            ],
            'taux_acceptation' => $reponses ? (int) round(100 * $acceptes / $reponses) : null,
            'reponses' => $reponses,
            'delai_moyen_reponse' => $delais->isNotEmpty() ? $this->duree($delais->avg()) : null,
            'donateurs_fideles' => $fideles->all(),
            'categories' => $categories->map(fn ($nombre, $libelle) => ['categorie' => $libelle, 'dons' => $nombre])->values()->all(),
        ];

        return [
            'donnees' => $donnees,
            'elements' => [['type' => 'statistiques', 'par_mois' => $donnees['par_mois'], 'total' => $donnees['total'],
                'taux_acceptation' => $donnees['taux_acceptation'], 'delai_moyen_reponse' => $donnees['delai_moyen_reponse']]],
        ];
    }

    /** Quand l'association a répondu : acceptation, ou refus. */
    private function dateDeReponse(Don $don): ?CarbonInterface
    {
        return match ($don->statut) {
            'accepte', 'remis' => $don->accepte_le,
            'refuse' => $don->cloture_le,
            default => null,
        };
    }

    /** « 40 minutes », « 5 heures », « 1,5 jour ». */
    private function duree(float $minutes): string
    {
        return match (true) {
            $minutes < 60 => max(1, (int) round($minutes)).' minutes',
            $minutes < 24 * 60 => (int) round($minutes / 60).' heures',
            default => str_replace('.', ',', (string) round($minutes / 1440, 1)).' jour'.(round($minutes / 1440, 1) >= 2 ? 's' : ''),
        };
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

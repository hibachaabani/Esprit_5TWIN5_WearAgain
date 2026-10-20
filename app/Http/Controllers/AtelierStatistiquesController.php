<?php

namespace App\Http\Controllers;

use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Reparation;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Statistiques et export de l'atelier : indicateurs chiffrés calculés sur les
 * données réelles (réparations, devis, délais, avis) — 100 % métier, sans IA.
 *
 * L'atelier ne voit et n'exporte que ses propres données.
 */
class AtelierStatistiquesController extends Controller
{
    /** Palette reprise de public/css/wearagain.css. */
    private const MAROON = '6B1A2A';

    private const GOLD = 'AD7F45';

    private const CREAM = 'F7F1E6';

    private const LINE = 'E6DECD';

    /** Couleurs des statuts, identiques aux classes .status-* de reparations.css. */
    private const STATUTS_COULEURS = [
        'en_attente' => ['fond' => 'ECE7DD', 'texte' => '6B6255'],
        'devis_propose' => ['fond' => 'F3E1BF', 'texte' => '8A5A1E'],
        'en_cours' => ['fond' => 'CDE0F5', 'texte' => '1F4E79'],
        'terminee' => ['fond' => 'D9E8D2', 'texte' => '2F5D1F'],
        'annulee' => ['fond' => 'F6D7D4', 'texte' => '8A231C'],
    ];

    public function __invoke(Request $request): View|RedirectResponse
    {
        $atelier = $request->user()->atelier;

        if (! $atelier) {
            return redirect()
                ->route('mon-atelier.edit')
                ->with('status', "Créez d'abord la fiche de votre atelier.");
        }

        return view('ateliers.statistiques', [
            'atelier' => $atelier,
            'reparations' => $this->statistiquesReparations($atelier),
            'avis' => $this->statistiquesAvis($atelier),
        ]);
    }

    /** Export Excel (.xlsx) coloré des réparations ou des avis de l'atelier. */
    public function export(Request $request, string $type)
    {
        $atelier = $request->user()->atelier;

        abort_unless($atelier !== null && in_array($type, ['reparations', 'avis'], true), 403);

        $fichier = 'wearagain-'.$type.'-'.$atelier->id.'.xlsx';

        return response()->streamDownload(
            function () use ($type, $atelier): void {
                $classeur = $type === 'reparations'
                    ? $this->construireReparations($atelier)
                    : $this->construireAvis($atelier);

                (new Xlsx($classeur))->save('php://output');

                $classeur->disconnectWorksheets();
            },
            $fichier,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /* ------------------------------------------------------------------ */
    /* Indicateurs */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function statistiquesReparations(Atelier $atelier): array
    {
        $reparations = $atelier->reparations()->get();

        $total = $reparations->count();
        $avecDevis = $reparations->filter(fn (Reparation $r) => $r->devis !== null);

        $delais = $reparations
            ->filter(fn (Reparation $r) => $r->date_rdv !== null && $r->created_at !== null)
            ->map(fn (Reparation $r) => round($r->created_at->diffInDays($r->date_rdv)));

        return [
            'total' => $total,
            'par_statut' => collect(Reparation::STATUTS)->map(
                fn (string $libelle, string $cle) => [
                    'cle' => $cle,
                    'libelle' => $libelle,
                    'total' => $reparations->where('statut', $cle)->count(),
                ]
            )->values(),
            'ca_estime' => round((float) $reparations->sum('devis'), 2),
            'devis_moyen' => $avecDevis->count() ? round((float) $avecDevis->avg('devis'), 2) : null,
            'taux_devis' => $total ? round($avecDevis->count() / $total * 100) : 0,
            'delai_moyen' => $delais->count() ? round((float) $delais->avg()) : null,
            'terminees' => $reparations->where('statut', 'terminee')->count(),
            'taux_terminees' => $total ? round($reparations->where('statut', 'terminee')->count() / $total * 100) : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statistiquesAvis(Atelier $atelier): array
    {
        $avis = $atelier->avis()->get();

        $total = $avis->count();
        $avecReponse = $avis->filter(fn (Avis $a) => $a->aReponseAtelier())->count();
        $positifs = $avis->where('note', '>=', 4)->count();
        $negatifs = $avis->where('note', '<=', 2)->count();

        return [
            'total' => $total,
            'note_moyenne' => $total ? round((float) $avis->avg('note'), 1) : null,
            'avec_reponse' => $avecReponse,
            'taux_reponse' => $total ? round($avecReponse / $total * 100) : 0,
            'positifs' => $positifs,
            'negatifs' => $negatifs,
            'neutres' => $total - $positifs - $negatifs,
            'pct_positifs' => $total ? round($positifs / $total * 100) : 0,
            'pct_negatifs' => $total ? round($negatifs / $total * 100) : 0,
            'repartition' => collect(range(1, 5))->reverse()->map(fn (int $note) => [
                'note' => $note,
                'total' => $avis->where('note', $note)->count(),
                'pct' => $total ? (int) round($avis->where('note', $note)->count() / $total * 100) : 0,
            ])->values(),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Export Excel (.xlsx) — mise en forme et couleurs WearAgain */
    /* ------------------------------------------------------------------ */

    private function construireReparations(Atelier $atelier): Spreadsheet
    {
        $stats = $this->statistiquesReparations($atelier);

        // Ordre de lecture : d'abord l'étape du parcours (annulée en dernier),
        // puis les plus récentes en premier à l'intérieur de chaque statut.
        $ordreStatuts = array_flip(Reparation::PARCOURS) + ['annulee' => count(Reparation::PARCOURS)];
        $reparations = $atelier->reparations()->with('vetement.user')->get()
            ->sortByDesc(fn (Reparation $r) => $r->created_at)
            ->sortBy(fn (Reparation $r) => $ordreStatuts[$r->statut] ?? 99)
            ->values();

        $classeur = new Spreadsheet;
        $feuille = $classeur->getActiveSheet();
        $feuille->setTitle('Réparations');
        $feuille->setShowGridlines(false);

        $colonnes = 14;

        $ligne = $this->titre($feuille, 1, $colonnes, 'WearAgain — Export des réparations');
        $ligne = $this->infos($feuille, $ligne, $colonnes, [
            'Atelier' => $atelier->nom,
            'Exporté le' => now()->format('d/m/Y H:i'),
            'Réparations' => $stats['total'],
        ]);

        $ligne = $this->section($feuille, $ligne, $colonnes, 'Résumé par statut');
        $ligne = $this->enTete($feuille, $ligne, ['Statut', 'Nombre']);
        foreach ($stats['par_statut'] as $s) {
            $feuille->setCellValue('A'.$ligne, $s['libelle']);
            $feuille->setCellValueExplicit('B'.$ligne, (int) $s['total'], DataType::TYPE_NUMERIC);

            $couleur = self::STATUTS_COULEURS[$s['cle']];
            $this->celluleColoree($feuille, 'A'.$ligne, $couleur['fond'], $couleur['texte']);
            $this->bordures($feuille, 'A'.$ligne.':B'.$ligne);

            $ligne++;
        }
        $ligne++;

        $ligne = $this->section($feuille, $ligne, $colonnes, 'Indicateurs');
        $ligne = $this->enTete($feuille, $ligne, ['Indicateur', 'Valeur']);
        $indicateurs = [
            ["Chiffre d'affaires estimé (DT)", $stats['ca_estime'], '#,##0.00'],
            ['Devis moyen (DT)', $stats['devis_moyen'], '#,##0.00'],
            ['Taux de devis (%)', $stats['taux_devis'], '0'],
            ['Délai moyen demande → RDV (j)', $stats['delai_moyen'], '0'],
            ['Taux de terminaison (%)', $stats['taux_terminees'], '0'],
        ];
        foreach ($indicateurs as [$libelle, $valeur, $format]) {
            $feuille->setCellValue('A'.$ligne, $libelle);
            $this->ecrireCellule($feuille, 'B'.$ligne, $valeur, $format);
            $feuille->getStyle('B'.$ligne)->getFont()->setBold(true);
            $this->bordures($feuille, 'A'.$ligne.':B'.$ligne);

            $ligne++;
        }
        $ligne++;

        $ligne = $this->section($feuille, $ligne, $colonnes, 'Détail des réparations');
        $ligne = $this->enTete($feuille, $ligne, [
            'ID', 'Créée le', 'Statut', 'Étape', 'Vêtement', 'Client',
            'Devis (DT)', 'Prix min IA (DT)', 'Prix max IA (DT)', 'Délai estimé IA (j)',
            'Date RDV', 'Délai demande → RDV (j)',
            'Catégorie IA', 'Gravité IA',
        ]);

        $formats = [
            1 => '0',
            2 => 'dd/mm/yyyy hh:mm',
            7 => '#,##0.00',
            8 => '#,##0.00',
            9 => '#,##0.00',
            10 => '0',
            11 => 'dd/mm/yyyy',
            12 => '0',
        ];

        foreach ($reparations as $r) {
            $etape = $r->etapeParcours();
            $delaiRdv = $r->date_rdv !== null && $r->created_at !== null
                ? (int) round($r->created_at->diffInDays($r->date_rdv))
                : null;

            $valeurs = [
                (int) $r->id,
                $r->created_at,
                $r->statutLabel(),
                $etape > 0 ? $etape.' / '.count(Reparation::PARCOURS) : '—',
                $r->vetement?->titre ?? '—',
                $r->vetement?->user?->name ?? '—',
                $r->devis !== null ? (float) $r->devis : null,
                $r->ia_prix_min !== null ? (float) $r->ia_prix_min : null,
                $r->ia_prix_max !== null ? (float) $r->ia_prix_max : null,
                $r->ia_delai_jours !== null ? (int) $r->ia_delai_jours : null,
                $r->date_rdv,
                $delaiRdv,
                $r->ia_categorie ?? '—',
                $r->iaGraviteLabel() ?? '—',
            ];

            foreach ($valeurs as $index => $valeur) {
                $this->ecrireCellule(
                    $feuille,
                    Coordinate::stringFromColumnIndex($index + 1).$ligne,
                    $valeur,
                    $formats[$index + 1] ?? null
                );
            }

            $couleur = self::STATUTS_COULEURS[$r->statut] ?? null;
            if ($couleur !== null) {
                $this->celluleColoree($feuille, 'C'.$ligne, $couleur['fond'], $couleur['texte']);
            }
            $this->bordures($feuille, 'A'.$ligne.':'.Coordinate::stringFromColumnIndex($colonnes).$ligne);

            $ligne++;
        }

        $this->largeurs($feuille, [
            1 => 6, 2 => 18, 3 => 16, 4 => 9, 5 => 24, 6 => 22, 7 => 13,
            8 => 16, 9 => 16, 10 => 17, 11 => 13, 12 => 18, 13 => 16, 14 => 14,
        ]);
        $feuille->freezePane('A2');

        return $classeur;
    }

    private function construireAvis(Atelier $atelier): Spreadsheet
    {
        $stats = $this->statistiquesAvis($atelier);

        $avis = $atelier->avis()->with('user')->get()
            ->sortByDesc(fn (Avis $a) => $a->date ?? $a->created_at)
            ->values();

        $classeur = new Spreadsheet;
        $feuille = $classeur->getActiveSheet();
        $feuille->setTitle('Avis');
        $feuille->setShowGridlines(false);

        $colonnes = 9;

        $ligne = $this->titre($feuille, 1, $colonnes, 'WearAgain — Export des avis');
        $ligne = $this->infos($feuille, $ligne, $colonnes, [
            'Atelier' => $atelier->nom,
            'Exporté le' => now()->format('d/m/Y H:i'),
            'Avis' => $stats['total'],
        ]);

        $ligne = $this->section($feuille, $ligne, $colonnes, 'Résumé');
        $ligne = $this->enTete($feuille, $ligne, ['Indicateur', 'Valeur']);
        $resume = [
            ['Note moyenne (sur 5)', $stats['note_moyenne'], '0.0', null],
            ['Taux de réponse (%)', $stats['taux_reponse'], '0', null],
            ['Avis positifs (note 4-5)', $stats['positifs'], '0', ['D9E8D2', '2F5D1F']],
            ['Avis neutres (note 3)', $stats['neutres'], '0', ['F3E1BF', '8A5A1E']],
            ['Avis négatifs (note 1-2)', $stats['negatifs'], '0', ['F6D7D4', '8A231C']],
        ];
        foreach ($resume as [$libelle, $valeur, $format, $couleur]) {
            $feuille->setCellValue('A'.$ligne, $libelle);
            $this->ecrireCellule($feuille, 'B'.$ligne, $valeur, $format);
            $feuille->getStyle('B'.$ligne)->getFont()->setBold(true);
            if ($couleur !== null) {
                $this->celluleColoree($feuille, 'B'.$ligne, $couleur[0], $couleur[1]);
            }
            $this->bordures($feuille, 'A'.$ligne.':B'.$ligne);

            $ligne++;
        }
        $ligne++;

        $ligne = $this->section($feuille, $ligne, $colonnes, 'Répartition par note');
        $ligne = $this->enTete($feuille, $ligne, ['Note', 'Nombre', 'Part (%)']);
        foreach ($stats['repartition'] as $n) {
            $feuille->setCellValueExplicit('A'.$ligne, (int) $n['note'], DataType::TYPE_NUMERIC);
            $feuille->setCellValueExplicit('B'.$ligne, (int) $n['total'], DataType::TYPE_NUMERIC);
            $feuille->setCellValueExplicit('C'.$ligne, (int) $n['pct'], DataType::TYPE_NUMERIC);

            $couleur = $this->couleurNote((int) $n['note']);
            $this->celluleColoree($feuille, 'A'.$ligne, $couleur['fond'], $couleur['texte']);
            $this->bordures($feuille, 'A'.$ligne.':C'.$ligne);

            $ligne++;
        }
        $ligne++;

        $ligne = $this->section($feuille, $ligne, $colonnes, 'Détail des avis');
        $ligne = $this->enTete($feuille, $ligne, [
            'ID', 'Date', 'Client', 'Note', 'Commentaire',
            'Réponse de l\'atelier', 'Répondu le', 'Assistée par IA', 'Risque IA',
        ]);

        $formats = [2 => 'dd/mm/yyyy', 4 => '0', 7 => 'dd/mm/yyyy'];

        foreach ($avis as $a) {
            $valeurs = [
                (int) $a->id,
                $a->date ?? $a->created_at,
                $a->user?->name ?? '—',
                (int) $a->note,
                (string) Str::of((string) $a->commentaire)->squish(),
                $a->aReponseAtelier() ? (string) Str::of((string) $a->reponse)->squish() : '—',
                $a->reponse_le,
                $a->reponse_ia ? 'oui' : 'non',
                $a->aAnalyseRisque() ? $a->risqueNiveauLabel() : '—',
            ];

            foreach ($valeurs as $index => $valeur) {
                $this->ecrireCellule(
                    $feuille,
                    Coordinate::stringFromColumnIndex($index + 1).$ligne,
                    $valeur,
                    $formats[$index + 1] ?? null
                );
            }

            $couleur = $this->couleurNote((int) $a->note);
            $this->celluleColoree($feuille, 'D'.$ligne, $couleur['fond'], $couleur['texte']);
            $this->bordures($feuille, 'A'.$ligne.':'.Coordinate::stringFromColumnIndex($colonnes).$ligne);

            $ligne++;
        }

        $this->largeurs($feuille, [
            1 => 6, 2 => 13, 3 => 22, 4 => 8, 5 => 45, 6 => 45, 7 => 13, 8 => 15, 9 => 13,
        ]);
        $feuille->freezePane('A2');

        return $classeur;
    }

    /** Bandeau titre : fond bordeaux, texte blanc gras. Renvoie la ligne suivante. */
    private function titre(Worksheet $feuille, int $ligne, int $colonnes, string $texte): int
    {
        $feuille->setCellValue('A'.$ligne, $texte);
        $feuille->mergeCells('A'.$ligne.':'.Coordinate::stringFromColumnIndex($colonnes).$ligne);

        $style = $feuille->getStyle('A'.$ligne);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::MAROON);
        $style->getFont()->setBold(true)->setSize(14)->setColor(new Color('FFFFFF'));
        $style->getAlignment()->setVertical('center');
        $feuille->getRowDimension($ligne)->setRowHeight(28);

        return $ligne + 1;
    }

    /** Bloc de contexte (libellé en crème + valeur), puis une ligne vide. */
    private function infos(Worksheet $feuille, int $ligne, int $colonnes, array $paires): int
    {
        $derniere = Coordinate::stringFromColumnIndex($colonnes);

        foreach ($paires as $libelle => $valeur) {
            $feuille->setCellValue('A'.$ligne, $libelle);
            $feuille->mergeCells('B'.$ligne.':'.$derniere.$ligne);
            $this->ecrireCellule($feuille, 'B'.$ligne, $valeur);

            $style = $feuille->getStyle('A'.$ligne);
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::CREAM);
            $style->getFont()->setBold(true);
            $this->bordures($feuille, 'A'.$ligne.':'.$derniere.$ligne);

            $ligne++;
        }

        return $ligne + 1;
    }

    /** Bandeau de section : fond or, texte blanc gras. */
    private function section(Worksheet $feuille, int $ligne, int $colonnes, string $texte): int
    {
        $feuille->setCellValue('A'.$ligne, $texte);
        $feuille->mergeCells('A'.$ligne.':'.Coordinate::stringFromColumnIndex($colonnes).$ligne);

        $style = $feuille->getStyle('A'.$ligne);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::GOLD);
        $style->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $style->getAlignment()->setVertical('center');
        $feuille->getRowDimension($ligne)->setRowHeight(22);

        return $ligne + 1;
    }

    /** En-tête de tableau : fond bordeaux, texte blanc gras, retour à la ligne. */
    private function enTete(Worksheet $feuille, int $ligne, array $libelles): int
    {
        foreach ($libelles as $index => $libelle) {
            $feuille->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$ligne, $libelle);
        }

        $plage = 'A'.$ligne.':'.Coordinate::stringFromColumnIndex(count($libelles)).$ligne;
        $style = $feuille->getStyle($plage);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::MAROON);
        $style->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $style->getAlignment()->setVertical('center')->setWrapText(true);
        $this->bordures($feuille, $plage);
        $feuille->getRowDimension($ligne)->setRowHeight(30);

        return $ligne + 1;
    }

    /** Écrit une valeur (texte, nombre, date) avec son éventuel format d'affichage. */
    private function ecrireCellule(Worksheet $feuille, string $coord, mixed $valeur, ?string $format = null): void
    {
        if ($valeur === null) {
            $feuille->setCellValue($coord, '—');

            return;
        }

        if ($valeur instanceof DateTimeInterface) {
            $feuille->setCellValue($coord, $valeur);
        } elseif (is_int($valeur) || is_float($valeur)) {
            $feuille->setCellValueExplicit($coord, $valeur, DataType::TYPE_NUMERIC);
        } else {
            $feuille->setCellValue($coord, $valeur);
        }

        if ($format !== null) {
            $feuille->getStyle($coord)->getNumberFormat()->setFormatCode($format);
        }
    }

    private function celluleColoree(Worksheet $feuille, string $coord, string $fond, string $texte): void
    {
        $style = $feuille->getStyle($coord);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fond);
        $style->getFont()->setColor(new Color($texte))->setBold(true);
    }

    private function bordures(Worksheet $feuille, string $plage): void
    {
        $bordures = $feuille->getStyle($plage)->getBorders()->getAllBorders();
        $bordures->setBorderStyle(Border::BORDER_THIN);
        $bordures->getColor()->setRGB(self::LINE);
    }

    private function largeurs(Worksheet $feuille, array $largeurs): void
    {
        foreach ($largeurs as $index => $largeur) {
            $feuille->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth($largeur);
        }
    }

    /** Couleur d'une note d'avis : vert (4-5), or (3), rouge (1-2). */
    private function couleurNote(int $note): array
    {
        return match (true) {
            $note >= 4 => ['fond' => 'D9E8D2', 'texte' => '2F5D1F'],
            $note === 3 => ['fond' => 'F3E1BF', 'texte' => '8A5A1E'],
            default => ['fond' => 'F6D7D4', 'texte' => '8A231C'],
        };
    }
}

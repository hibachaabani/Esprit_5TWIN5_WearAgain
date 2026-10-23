<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel des dons reçus par une association : une feuille « Dons » (le détail)
 * et une feuille « Bilan des besoins » (demandés, reçus, en route, restants).
 * Pour un rapport d'activité ; l'association n'exporte que ses propres données.
 */
class ExportDonsAssociation
{
    /** Palette reprise de public/css/wearagain.css. */
    private const MAROON = '6B1A2A';

    private const CREAM = 'F7F1E6';

    private const LINE = 'E6DECD';

    /** Couleurs des statuts de don : fond et texte. */
    private const COULEURS_STATUT = [
        'propose' => ['F3E1BF', '8A5A1E'],
        'accepte' => ['CDE0F5', '1F4E79'],
        'remis' => ['D9E8D2', '2F5D1F'],
        'refuse' => ['F6D7D4', '8A231C'],
        'annule' => ['ECE7DD', '6B6255'],
    ];

    public function classeur(Association $association): Spreadsheet
    {
        $classeur = new Spreadsheet;
        $classeur->getProperties()->setCreator('WearAgain')->setTitle('Dons reçus — '.$association->nom);

        $this->feuilleDons($classeur->getActiveSheet(), $association);
        $this->feuilleBesoins($classeur->createSheet(), $association);
        $classeur->setActiveSheetIndex(0);

        return $classeur;
    }

    /** Nom du fichier : dons-<association>-<date>.xlsx */
    public function nomFichier(Association $association): string
    {
        return 'dons-'.str($association->nom)->slug().'-'.now()->format('Y-m-d').'.xlsx';
    }

    private function feuilleDons(Worksheet $feuille, Association $association): void
    {
        $dons = $association->dons()->with(['donateur', 'besoin'])->latest()->latest('id')->get();
        $compteurs = $dons->countBy('statut');

        $feuille->setTitle('Dons');
        $feuille->setShowGridlines(false);

        $entetes = ['N°', 'Proposé le', 'Donateur', 'Vêtement', 'Besoin', 'Mode', 'Statut', 'Remise prévue le', 'Lieu de remise', 'Clôturé le', 'Motif du refus'];
        $ligne = $this->titre($feuille, count($entetes), 'Dons reçus — '.$association->nom);

        // Résumé : total puis un nombre par statut.
        $resume = ['Exporté le' => now()->format('d/m/Y H:i'), 'Dons au total' => $dons->count()];
        foreach (Don::LIBELLES_STATUT as $statut => $libelle) {
            $resume[$libelle] = $compteurs[$statut] ?? 0;
        }
        foreach ($resume as $libelle => $valeur) {
            // Libellé sur les deux premières colonnes (étroites), valeur dans la troisième.
            $feuille->setCellValue('A'.$ligne, $libelle);
            $feuille->mergeCells('A'.$ligne.':B'.$ligne);
            $this->ecrire($feuille, 'C'.$ligne, $valeur);
            $feuille->getStyle('C'.$ligne)->getAlignment()->setHorizontal('left');
            $this->fond($feuille, 'A'.$ligne, self::CREAM)->getFont()->setBold(true);
            $this->bordures($feuille, 'A'.$ligne.':C'.$ligne);
            $ligne++;
        }
        $ligne++;

        $debut = $ligne = $this->enTete($feuille, $ligne, $entetes);

        foreach ($dons as $don) {
            $this->ligne($feuille, $ligne, [
                $don->id,
                $don->created_at,
                $don->donateur?->name ?? 'Compte supprimé',
                $don->vetement_titre,
                $don->besoin?->titre ?? 'Don libre',
                Don::LIBELLES_MODE[$don->mode] ?? $don->mode,
                Don::LIBELLES_STATUT[$don->statut] ?? $don->statut,
                $don->date_remise,
                $don->lieu_remise,
                $don->cloture_le,
                $don->motif_refus,
            ], [2 => 'dd/mm/yyyy', 8 => 'dd/mm/yyyy hh:mm', 10 => 'dd/mm/yyyy']);

            [$fond, $texte] = self::COULEURS_STATUT[$don->statut] ?? [self::CREAM, '000000'];
            $this->fond($feuille, 'G'.$ligne, $fond)->getFont()->setBold(true)->setColor(new Color($texte));
            $ligne++;
        }

        if ($dons->isEmpty()) {
            $feuille->setCellValue('A'.$ligne, 'Aucun don reçu pour le moment.');
        } else {
            $feuille->setAutoFilter('A'.($debut - 1).':K'.($ligne - 1));
        }

        $this->largeurs($feuille, [6, 12, 22, 30, 30, 18, 11, 17, 28, 12, 30]);
        $feuille->freezePane('A'.$debut);
    }

    private function feuilleBesoins(Worksheet $feuille, Association $association): void
    {
        $besoins = $association->besoins()->avecProgression()->parPriorite()->get();

        $feuille->setTitle('Bilan des besoins');
        $feuille->setShowGridlines(false);

        $entetes = ['Besoin', 'Catégorie', 'Urgence', 'Date limite', 'Statut', 'Demandés', 'Reçus', 'En route', 'Restants', 'Atteint'];
        $ligne = $this->titre($feuille, count($entetes), 'Bilan des besoins — '.$association->nom);
        $debut = $ligne = $this->enTete($feuille, $ligne + 1, $entetes);

        foreach ($besoins as $besoin) {
            $this->ligne($feuille, $ligne, [
                $besoin->titre,
                Association::CATEGORIES[$besoin->categorie] ?? $besoin->categorie,
                Besoin::URGENCES[$besoin->urgence] ?? $besoin->urgence,
                $besoin->date_limite,
                $besoin->libelleStatut(),
                $besoin->quantite,
                $besoin->quantiteRecue(),
                $besoin->quantiteEnRoute(),
                $besoin->quantiteRestante(),
                // Part de l'objectif déjà remise, affichée en pourcentage par Excel.
                $besoin->quantite > 0 ? min(1, $besoin->quantiteRecue() / $besoin->quantite) : 0,
            ], [4 => 'dd/mm/yyyy', 10 => '0%']);
            $ligne++;
        }

        if ($besoins->isEmpty()) {
            $feuille->setCellValue('A'.$ligne, 'Aucun besoin publié pour le moment.');
        } else {
            // Ligne des totaux, calculée par Excel.
            $feuille->setCellValue('A'.$ligne, 'Total');
            foreach (['F', 'G', 'H', 'I'] as $colonne) {
                $feuille->setCellValue($colonne.$ligne, "=SUM({$colonne}{$debut}:{$colonne}".($ligne - 1).')');
            }
            $feuille->setCellValue('J'.$ligne, "=IF(F{$ligne}>0,MIN(1,G{$ligne}/F{$ligne}),0)");
            $feuille->getStyle('J'.$ligne)->getNumberFormat()->setFormatCode('0%');
            $this->fond($feuille, 'A'.$ligne.':J'.$ligne, self::CREAM)->getFont()->setBold(true);
            $this->bordures($feuille, 'A'.$ligne.':J'.$ligne);
        }

        $this->largeurs($feuille, [32, 16, 11, 12, 11, 11, 9, 10, 10, 9]);
        $feuille->freezePane('A'.$debut);
    }

    /** Bandeau titre bordeaux sur toute la largeur. Renvoie la ligne suivante. */
    private function titre(Worksheet $feuille, int $colonnes, string $texte): int
    {
        $feuille->setCellValue('A1', $texte);
        $feuille->mergeCells('A1:'.Coordinate::stringFromColumnIndex($colonnes).'1');
        $this->fond($feuille, 'A1', self::MAROON)->getFont()->setBold(true)->setSize(14)->setColor(new Color('FFFFFF'));
        $feuille->getStyle('A1')->getAlignment()->setVertical('center');
        $feuille->getRowDimension(1)->setRowHeight(28);

        return 3;
    }

    /** En-tête de tableau bordeaux. Renvoie la première ligne de données. */
    private function enTete(Worksheet $feuille, int $ligne, array $libelles): int
    {
        foreach ($libelles as $index => $libelle) {
            $feuille->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$ligne, $libelle);
        }

        $plage = 'A'.$ligne.':'.Coordinate::stringFromColumnIndex(count($libelles)).$ligne;
        $this->fond($feuille, $plage, self::MAROON)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $feuille->getStyle($plage)->getAlignment()->setVertical('center')->setWrapText(true);
        $this->bordures($feuille, $plage);
        $feuille->getRowDimension($ligne)->setRowHeight(24);

        return $ligne + 1;
    }

    /** Écrit une ligne de valeurs, avec les formats par numéro de colonne. */
    private function ligne(Worksheet $feuille, int $ligne, array $valeurs, array $formats = []): void
    {
        foreach ($valeurs as $index => $valeur) {
            $coord = Coordinate::stringFromColumnIndex($index + 1).$ligne;
            $this->ecrire($feuille, $coord, $valeur);

            if (isset($formats[$index + 1]) && $valeur !== null) {
                $feuille->getStyle($coord)->getNumberFormat()->setFormatCode($formats[$index + 1]);
            }
        }

        $this->bordures($feuille, 'A'.$ligne.':'.Coordinate::stringFromColumnIndex(count($valeurs)).$ligne);
    }

    private function ecrire(Worksheet $feuille, string $coord, mixed $valeur): void
    {
        match (true) {
            $valeur === null || $valeur === '' => $feuille->setCellValue($coord, '—'),
            // Vraie date Excel (et non du texte) : le format jj/mm/aaaa s'applique et la colonne se trie.
            $valeur instanceof DateTimeInterface => $feuille->setCellValueExplicit($coord, Date::PHPToExcel($valeur), DataType::TYPE_NUMERIC),
            is_int($valeur) || is_float($valeur) => $feuille->setCellValueExplicit($coord, $valeur, DataType::TYPE_NUMERIC),
            // Texte saisi par un utilisateur : jamais interprété comme une formule.
            default => $feuille->setCellValueExplicit($coord, (string) $valeur, DataType::TYPE_STRING),
        };
    }

    private function fond(Worksheet $feuille, string $plage, string $couleur): Style
    {
        $style = $feuille->getStyle($plage);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($couleur);

        return $style;
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
            $feuille->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($largeur);
        }
    }
}

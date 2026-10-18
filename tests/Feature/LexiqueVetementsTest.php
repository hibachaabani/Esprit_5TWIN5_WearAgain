<?php

namespace Tests\Feature;

use App\Services\IA\Dons\AnalyseurDonLocal;
use App\Services\IA\Dons\LexiqueVetements;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LexiqueVetementsTest extends TestCase
{
    public static function vetements(): array
    {
        return [
            'claquettes' => ['Claquettes pointure 40', 'chaussures', 'chaussure', 'ete'],
            'montre' => ['Une montre homme', 'accessoires', 'bijou', 'toutes'],
            'chapeau' => ['Chapeau de paille', 'accessoires', 'accessoire', 'toutes'],
            'maillot de bain' => ['Maillots de bain enfants', 'enfant', 'maillotbain', 'ete'],
            'sac à main' => ['Sac à main en cuir', 'accessoires', 'sac', 'toutes'],
            'kachabia' => ['Kachabia en laine', 'homme', 'traditionnel', 'hiver'],
            'sefsari' => ['Sefsari blanc', 'femme', 'traditionnel', 'toutes'],
            'tennis' => ['Tennis blanches', 'chaussures', 'chaussure', 'toutes'],
            'gigoteuse' => ['Gigoteuse 6 mois', 'bebe', 'pyjama', 'toutes'],
            'taie' => ["Taies d'oreiller", 'linge', 'linge', 'toutes'],
        ];
    }

    #[DataProvider('vetements')]
    public function test_le_lexique_reconnait_categorie_famille_et_saison(string $texte, string $categorie, string $famille, string $saison): void
    {
        $analyse = (new AnalyseurDonLocal())->analyser($texte);

        $this->assertSame($categorie, $analyse['categorie']);
        $this->assertContains($famille, LexiqueVetements::familles(LexiqueVetements::mots($texte)));
        $this->assertSame($saison, $analyse['saison']);
    }

    public function test_une_expression_n_est_pas_confondue_avec_ses_mots(): void
    {
        // Une robe de chambre n'est pas une robe, un maillot de bain n'est pas un maillot de sport.
        $this->assertSame(['pyjama'], LexiqueVetements::familles(LexiqueVetements::mots('Robe de chambre')));
        $this->assertSame(['maillotbain'], LexiqueVetements::familles(LexiqueVetements::mots('maillot de bain')));
    }

    public function test_le_pluriel_et_les_mots_invariables(): void
    {
        $this->assertSame(['chaussure', 'chapeau', 'tennis'], LexiqueVetements::mots('Chaussures, chapeaux et tennis'));
        $this->assertSame(['lunettedesoleil'], LexiqueVetements::mots('lunette de soleil'));
        $this->assertSame(['lunettedesoleil'], LexiqueVetements::mots('Lunettes de soleil'));
    }
}

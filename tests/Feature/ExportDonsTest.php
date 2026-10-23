<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Models\User;
use App\Services\ExportDonsAssociation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportDonsTest extends TestCase
{
    use RefreshDatabase;

    private function don(Association $association, array $attributs = []): Don
    {
        return Don::factory()->create($attributs + [
            'association_id' => $association->id,
            'donateur_id' => User::factory()->create()->id,
            'statut' => 'propose',
            'mode' => 'depot',
        ]);
    }

    public function test_l_association_telecharge_un_fichier_excel(): void
    {
        $association = Association::factory()->create(['verifiee' => true, 'nom' => 'Les Mains Solidaires']);

        $reponse = $this->actingAs($association->user)->get(route('dons.recus.export'));

        $reponse->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload('dons-les-mains-solidaires-'.now()->format('Y-m-d').'.xlsx');
        // Un fichier .xlsx est une archive zip.
        $this->assertStringStartsWith('PK', $reponse->streamedContent());
    }

    public function test_le_fichier_contient_seulement_les_dons_de_l_association(): void
    {
        $association = Association::factory()->create(['verifiee' => true]);
        $this->don($association, ['vetement_titre' => 'Manteau bleu 8 ans', 'statut' => 'remis']);
        $this->don($association, ['vetement_titre' => 'Pull en laine']);
        $this->don(Association::factory()->create(['verifiee' => true]), ['vetement_titre' => 'Robe d\'une autre association']);

        $feuille = app(ExportDonsAssociation::class)->classeur($association)->getSheetByName('Dons');
        $vetements = array_column($feuille->toArray(), 3);

        $this->assertContains('Manteau bleu 8 ans', $vetements);
        $this->assertContains('Pull en laine', $vetements);
        $this->assertNotContains('Robe d\'une autre association', $vetements);
        $this->assertSame(2, $feuille->getCell('C4')->getValue());
    }

    public function test_le_bilan_compte_les_vetements_recus_et_en_route(): void
    {
        $association = Association::factory()->create(['verifiee' => true]);
        $besoin = Besoin::factory()->for($association)->create(['titre' => 'Manteaux enfants', 'quantite' => 4, 'statut' => 'ouvert', 'date_limite' => '2026-12-15']);
        $this->don($association, ['besoin_id' => $besoin->id, 'statut' => 'remis']);
        $this->don($association, ['besoin_id' => $besoin->id, 'statut' => 'accepte']);

        $feuille = app(ExportDonsAssociation::class)->classeur($association)->getSheetByName('Bilan des besoins');

        // Ligne 5 : le besoin (titre en ligne 1, en-tête en ligne 4).
        $this->assertSame('Manteaux enfants', $feuille->getCell('A5')->getValue());
        $this->assertSame([4, 1, 1, 2], [
            $feuille->getCell('F5')->getValue(), $feuille->getCell('G5')->getValue(),
            $feuille->getCell('H5')->getValue(), $feuille->getCell('I5')->getValue(),
        ]);
        $this->assertSame(0.25, $feuille->getCell('J5')->getValue());
        // Une vraie date Excel, affichée au format français.
        $this->assertSame('15/12/2026', $feuille->getCell('D5')->getFormattedValue());
    }

    public function test_un_texte_qui_ressemble_a_une_formule_reste_du_texte(): void
    {
        $association = Association::factory()->create(['verifiee' => true]);
        $this->don($association, ['vetement_titre' => '=HYPERLINK("http://exemple.test")']);

        $feuille = app(ExportDonsAssociation::class)->classeur($association)->getSheetByName('Dons');

        $this->assertSame('s', $feuille->getCell('D12')->getDataType());
    }

    public function test_seules_les_associations_peuvent_exporter(): void
    {
        $this->get(route('dons.recus.export'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('dons.recus.export'))->assertForbidden();
    }

    public function test_le_bouton_est_sur_la_page_des_dons_recus(): void
    {
        $association = Association::factory()->create(['verifiee' => true]);

        $this->actingAs($association->user)->get(route('dons.recus'))
            ->assertOk()
            ->assertSee(route('dons.recus.export'))
            ->assertSee('Exporter en Excel');
    }
}

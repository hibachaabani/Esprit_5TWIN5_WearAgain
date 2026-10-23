<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Atelier;
use App\Models\Besoin;
use App\Models\Don;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccueilTest extends TestCase
{
    use RefreshDatabase;

    private function besoin(array $attributs = [], array $association = []): Besoin
    {
        return Besoin::factory()
            ->for(Association::factory()->create($association + ['verifiee' => true]))
            ->create($attributs + ['statut' => 'ouvert', 'date_limite' => null, 'quantite' => 10]);
    }

    public function test_les_besoins_urgents_des_associations_verifiees_sont_mis_en_avant(): void
    {
        $this->besoin(['titre' => 'Manteaux pour la Kasbah', 'urgence' => 'urgente']);
        $this->besoin(['titre' => 'Besoin non vérifié', 'urgence' => 'urgente'], ['verifiee' => false]);
        $complet = $this->besoin(['titre' => 'Besoin déjà couvert', 'urgence' => 'urgente', 'quantite' => 1]);
        Don::factory()->create(['besoin_id' => $complet->id, 'association_id' => $complet->association_id, 'statut' => 'accepte']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ils en ont besoin maintenant')
            ->assertSee('Manteaux pour la Kasbah')
            ->assertDontSee('Besoin non vérifié')
            ->assertDontSee('Besoin déjà couvert');
    }

    public function test_les_chiffres_viennent_de_la_base(): void
    {
        $besoin = $this->besoin();
        Don::factory()->count(3)->create(['besoin_id' => $besoin->id, 'association_id' => $besoin->association_id, 'statut' => 'remis']);

        $this->get(route('home'))
            ->assertSeeInOrder(['<strong>3</strong><span>dons remis aux associations', '<strong>1</strong><span>associations vérifiées', '<strong>1</strong><span>besoins ouverts'], false);
    }

    public function test_un_visiteur_peut_demander_a_l_assistant_de_don(): void
    {
        $this->get(route('home'))
            ->assertSee('Assistant de don IA')
            ->assertSee('action="'.route('besoins.assistant').'"', false)
            ->assertSee(e(route('besoins.assistant', ['description' => 'Doudoune bleue 8 ans'])), false)
            ->assertDontSee(route('agent.index'));
    }

    public function test_l_association_voit_ses_outils_ia_et_pas_l_assistant_de_don(): void
    {
        $association = Association::factory()->create(['verifiee' => true]);

        $this->actingAs($association->user)->get(route('home'))
            ->assertSee('Vos outils IA')
            ->assertSee(route('agent.index'))
            ->assertDontSee('Assistant de don IA');
    }

    public function test_l_atelier_ne_voit_aucun_bandeau_de_don(): void
    {
        $this->actingAs(Atelier::factory()->create()->user)->get(route('home'))
            ->assertOk()
            ->assertDontSee('Assistant de don IA')
            ->assertDontSee('Vos outils IA')
            ->assertDontSee(route('reparations.create'));
    }

    public function test_le_particulier_a_les_liens_vers_ses_outils_ia(): void
    {
        $this->actingAs(User::factory()->create())->get(route('home'))
            ->assertSee('Assistant de don IA')
            ->assertSee(route('reparations.create'))
            ->assertSee(route('vetements.create'));
    }
}

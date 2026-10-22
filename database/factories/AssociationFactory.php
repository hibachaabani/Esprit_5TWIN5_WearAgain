<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssociationFactory extends Factory
{
    /** Données de démonstration crédibles : associations tunisiennes, en français. */
    private const NOMS = ['Association Espoir', 'Entraide Solidaire', 'Les Amis du Quartier', 'Association Nour', 'Cœur Ouvert',
        'Association El Amal', 'Main dans la Main', 'Association Tadamon', 'Vêtements du Cœur', 'Association Sanad', 'Les Petits Pas', 'Association Rahma'];

    private const MISSIONS = [
        'Distribution de vêtements et de produits de première nécessité aux familles en difficulté.',
        'Accompagnement des enfants scolarisés : fournitures, vêtements de rentrée et soutien scolaire.',
        "Aide d'urgence aux familles sans abri pendant l'hiver : manteaux, couvertures et repas chauds.",
        'Soutien aux jeunes mamans : layette, vêtements de bébé et ateliers de puériculture.',
        'Collecte et redistribution de vêtements dans les quartiers populaires, avec une boutique solidaire.',
        'Insertion professionnelle : tenues pour les entretiens d\'embauche et accompagnement vers l\'emploi.',
        'Aide aux personnes âgées isolées : visites, vêtements chauds et linge de maison.',
        'Accueil des familles des zones rurales : vêtements, chaussures et colis pour la rentrée.',
    ];

    /** Quartiers ajoutés au nom : deux associations générées n'ont jamais le même nom (les tests comparent les noms). */
    private const QUARTIERS = ['Lac', 'Menzah', 'Médina', 'Ennasr', 'Marsa', 'Bardo', 'Manar', 'Carthage', 'Mourouj', 'Ariana Ville'];

    private static int $numero = 0;

    private const ADRESSES = ['12 avenue Habib Bourguiba', '5 rue de la Liberté', '23 rue Ibn Khaldoun', '8 avenue de la République',
        '17 rue Hédi Chaker', '40 avenue Farhat Hached', '3 rue de Marseille', '9 rue Mongi Slim'];

    /** « Association Nour Lac », « Entraide Solidaire Lac »… puis le quartier suivant : jamais deux fois le même nom. */
    private function nomUnique(): string
    {
        $n = self::$numero++;
        $tour = intdiv($n, count(self::NOMS));
        $nom = self::NOMS[$n % count(self::NOMS)].' '.self::QUARTIERS[$tour % count(self::QUARTIERS)];

        return $tour < count(self::QUARTIERS) ? $nom : $nom.' '.(intdiv($tour, count(self::QUARTIERS)) + 1);
    }

    public function definition(): array
    {
        $ville = fake()->randomElement(['Tunis', 'Sousse', 'Sfax', 'Ariana', 'Nabeul', 'Bizerte']);
        [$lat, $lng] = config('villes')[$ville];

        return [
            'user_id' => User::factory()->association(),
            'nom' => $this->nomUnique(),
            'mission' => fake()->randomElement(self::MISSIONS),
            'categories' => fake()->randomElements(array_keys(Association::CATEGORIES), random_int(2, 5)),
            'adresse' => fake()->randomElement(self::ADRESSES),
            'ville' => $ville,
            // Dispersion d'environ 3 km autour du centre-ville.
            'latitude' => $lat + fake()->randomFloat(4, -0.03, 0.03),
            'longitude' => $lng + fake()->randomFloat(4, -0.03, 0.03),
            'numero_agrement' => 'AGR-'.fake()->unique()->numerify('####-####'),
            'verifiee' => fake()->boolean(70),
        ];
    }
}

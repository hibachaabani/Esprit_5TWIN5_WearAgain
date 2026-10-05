<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\User;
use Illuminate\Database\Seeder;

class CollecteSeeder extends Seeder
{
    /** Points de collecte de démonstration, répartis sur plusieurs villes. */
    private const POINTS = [
        ['nom' => 'Borne textile Lafayette', 'adresse' => 'Rue de Palestine', 'ville' => 'Tunis', 'latitude' => 36.8190, 'longitude' => 10.1800, 'horaires' => 'Tous les jours 8h–20h', 'capacite' => 60],
        ['nom' => 'Point relais Bab Bhar', 'adresse' => 'Place de la Victoire', 'ville' => 'Tunis', 'latitude' => 36.7990, 'longitude' => 10.1700, 'horaires' => 'Lun–Sam 9h–18h', 'capacite' => 40],
        ['nom' => 'Local solidaire Menzah 6', 'adresse' => 'Avenue Othman Ibn Affane', 'ville' => 'Ariana', 'latitude' => 36.8460, 'longitude' => 10.1760, 'horaires' => 'Lun–Ven 10h–17h', 'capacite' => 80],
        ['nom' => 'Conteneur Ennasr', 'adresse' => 'Avenue Hédi Nouira', 'ville' => 'Ariana', 'latitude' => 36.8590, 'longitude' => 10.1650, 'horaires' => 'Tous les jours 7h–22h', 'capacite' => 12],
        ['nom' => 'Local solidaire La Marsa', 'adresse' => 'Avenue Habib Bourguiba', 'ville' => 'La Marsa', 'latitude' => 36.8782, 'longitude' => 10.3247, 'horaires' => 'Mar–Dim 9h–13h / 15h–19h', 'capacite' => 50],
        ['nom' => 'Borne textile Sousse Centre', 'adresse' => 'Avenue Léopold Sédar Senghor', 'ville' => 'Sousse', 'latitude' => 35.8280, 'longitude' => 10.6370, 'horaires' => 'Lun–Sam 9h–18h', 'capacite' => 70],
        ['nom' => 'Point relais Sfax Médina', 'adresse' => 'Rue Mongi Slim', 'ville' => 'Sfax', 'latitude' => 34.7380, 'longitude' => 10.7600, 'horaires' => 'Lun–Sam 8h–17h', 'capacite' => 60],
        ['nom' => 'Local solidaire Nabeul', 'adresse' => 'Avenue Farhat Hached', 'ville' => 'Nabeul', 'latitude' => 36.4540, 'longitude' => 10.7350, 'horaires' => 'Lun–Ven 9h–16h', 'capacite' => 30],
        ['nom' => 'Conteneur Monastir Marina', 'adresse' => 'Route de la Corniche', 'ville' => 'Monastir', 'latitude' => 35.7700, 'longitude' => 10.8300, 'horaires' => 'Tous les jours 8h–20h', 'capacite' => 40],
        ['nom' => 'Borne textile Bizerte Vieux Port', 'adresse' => 'Quai Tarak Ibn Ziad', 'ville' => 'Bizerte', 'latitude' => 37.2720, 'longitude' => 9.8730, 'horaires' => 'Fermé pour travaux', 'capacite' => 40, 'actif' => false],
    ];

    public function run(): void
    {
        $particuliers = User::factory(8)->create();

        foreach (self::POINTS as $donnees) {
            $point = PointCollecte::factory()->create($donnees);

            // Des dépôts à chaque étape du parcours, rattachés aux particuliers créés plus haut.
            $repartition = $point->actif
                ? ['enAttente' => rand(1, 4), 'recu' => rand(0, 3), 'trie' => rand(2, 5), 'annule' => rand(0, 1)]
                : ['trie' => rand(3, 6)];

            foreach ($repartition as $etat => $nombre) {
                Depot::factory($nombre)->{$etat}()->for($point)->recycle($particuliers)->create();
            }
        }

        // Le petit conteneur d'Ennasr est plein : il illustre le refus de dépôt.
        $ennasr = PointCollecte::where('nom', 'Conteneur Ennasr')->first();
        $manquants = $ennasr->placesRestantes();
        if ($manquants > 0) {
            Depot::factory($manquants)->enAttente()->for($ennasr)->recycle($particuliers)->create();
        }
    }
}

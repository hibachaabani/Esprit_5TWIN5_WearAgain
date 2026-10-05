<?php

/*
|--------------------------------------------------------------------------
| Module collecte (points de collecte et dépôts)
|--------------------------------------------------------------------------
*/

return [

    // Un dépôt jamais apporté est annulé automatiquement ce nombre de jours
    // après la date prévue (commande planifiée depots:expirer).
    'expiration_jours' => (int) env('COLLECTE_EXPIRATION_JOURS', 7),

    // À partir de ce taux de remplissage (%), un point est signalé « à vider ».
    'seuil_alerte' => (int) env('COLLECTE_SEUIL_ALERTE', 80),

    // Vitesse moyenne en ville (km/h) pour estimer la durée d'une tournée.
    'vitesse_tournee' => 30,

    // Hypothèses du calcul d'impact : ordres de grandeur indicatifs, à ajuster.
    'impact' => [
        'poids_moyen_kg' => 0.5,          // poids moyen d'un vêtement
        'co2_evite_kg_par_kg' => 10,      // CO2e évité par kg de textile réemployé
        'eau_litres_par_vetement' => 2700, // eau nécessaire pour produire un t-shirt en coton
    ],

];

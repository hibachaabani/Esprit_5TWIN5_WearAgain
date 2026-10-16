<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Réduit le cycle de vie des réparations à 5 statuts.
 *
 * Ancien -> nouveau :
 *   devis_accepte -> en_cours
 *   devis_refuse  -> en_attente
 *   livree        -> terminee
 *
 * Les valeurs d'historique (reparation_suivis) sont alignées de la même façon.
 */
return new class extends Migration
{
    private const CORRESPONDANCE = [
        'devis_accepte' => 'en_cours',
        'devis_refuse' => 'en_attente',
        'livree' => 'terminee',
    ];

    public function up(): void
    {
        foreach (self::CORRESPONDANCE as $ancien => $nouveau) {
            DB::table('reparations')->where('statut', $ancien)->update(['statut' => $nouveau]);
            DB::table('reparation_suivis')->where('ancien_statut', $ancien)->update(['ancien_statut' => $nouveau]);
            DB::table('reparation_suivis')->where('nouveau_statut', $ancien)->update(['nouveau_statut' => $nouveau]);
        }
    }

    public function down(): void
    {
        // Donnée irréversible : la distinction d'origine est perdue.
    }
};

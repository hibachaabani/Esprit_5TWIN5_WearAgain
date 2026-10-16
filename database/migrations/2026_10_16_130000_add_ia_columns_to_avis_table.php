<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Réparations & Avis (membre 5).
 *
 * - Réponse de l'atelier à un avis (texte + horodatage + origine IA).
 * - Détection assistée par IA des avis suspects (score, niveau, signaux).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Réponse publique de l'atelier.
            $table->text('reponse')->nullable()->after('commentaire');
            $table->timestamp('reponse_le')->nullable()->after('reponse');
            $table->boolean('reponse_ia')->default(false)->after('reponse_le');

            // Analyse de risque (faux avis).
            $table->unsignedTinyInteger('ia_risque_score')->nullable()->after('reponse_ia');
            $table->string('ia_risque_niveau')->nullable()->after('ia_risque_score');
            $table->json('ia_signaux')->nullable()->after('ia_risque_niveau');
            $table->timestamp('ia_analyse_le')->nullable()->after('ia_signaux');
        });
    }

    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->dropColumn([
                'reponse', 'reponse_le', 'reponse_ia',
                'ia_risque_score', 'ia_risque_niveau', 'ia_signaux', 'ia_analyse_le',
            ]);
        });
    }
};

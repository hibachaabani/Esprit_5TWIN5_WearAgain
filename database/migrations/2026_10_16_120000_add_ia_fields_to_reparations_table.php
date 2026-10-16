<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Champs du diagnostic assisté par IA sur les demandes de réparation
     * (catégorie, gravité, fourchette de prix, délai, matériaux, compétences).
     */
    public function up(): void
    {
        Schema::table('reparations', function (Blueprint $table) {
            $table->string('ia_categorie')->nullable()->after('date_rdv');
            $table->string('ia_gravite')->nullable()->after('ia_categorie');
            $table->decimal('ia_prix_min', 8, 2)->nullable()->after('ia_gravite');
            $table->decimal('ia_prix_max', 8, 2)->nullable()->after('ia_prix_min');
            $table->unsignedTinyInteger('ia_delai_jours')->nullable()->after('ia_prix_max');
            $table->json('ia_materiaux')->nullable()->after('ia_delai_jours');
            $table->json('ia_competences')->nullable()->after('ia_materiaux');
            $table->text('ia_recommandation')->nullable()->after('ia_competences');
            $table->string('ia_source')->nullable()->after('ia_recommandation');
            $table->timestamp('ia_analyse_le')->nullable()->after('ia_source');
        });
    }

    public function down(): void
    {
        Schema::table('reparations', function (Blueprint $table) {
            $table->dropColumn([
                'ia_categorie',
                'ia_gravite',
                'ia_prix_min',
                'ia_prix_max',
                'ia_delai_jours',
                'ia_materiaux',
                'ia_competences',
                'ia_recommandation',
                'ia_source',
                'ia_analyse_le',
            ]);
        });
    }
};

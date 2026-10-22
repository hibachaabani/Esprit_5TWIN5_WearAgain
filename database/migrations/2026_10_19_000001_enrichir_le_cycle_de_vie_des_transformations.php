<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Cycle de vie enrichi d'une transformation : devis (montant + délai + message),
| réponse du particulier, validation du résultat et annulation motivée.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transformations', function (Blueprint $table) {
            // Devis proposé par l'atelier
            $table->decimal('devis_prix', 8, 2)->nullable()->after('prix');
            $table->unsignedSmallInteger('devis_delai_jours')->nullable()->after('devis_prix');
            $table->text('devis_message')->nullable()->after('devis_delai_jours');
            $table->timestamp('devis_envoye_le')->nullable()->after('devis_message');
            $table->timestamp('devis_repondu_le')->nullable()->after('devis_envoye_le');

            // Validation du résultat par le particulier
            $table->timestamp('resultat_valide_le')->nullable()->after('image_resultat');

            // Annulation motivée (par le particulier ou l'atelier)
            $table->text('motif_annulation')->nullable()->after('statut');
            $table->string('annule_par', 20)->nullable()->after('motif_annulation');
        });

        // Données existantes : les transformations déjà en cours ou terminées ont un
        // prix fixé par l'atelier — on le reporte comme devis accepté pour l'affichage.
        DB::table('transformations')
            ->whereIn('statut', ['en_cours', 'terminee'])
            ->where('prix', '>', 0)
            ->update([
                'devis_prix' => DB::raw('prix'),
                'devis_envoye_le' => DB::raw('created_at'),
                'devis_repondu_le' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('transformations', function (Blueprint $table) {
            $table->dropColumn([
                'devis_prix', 'devis_delai_jours', 'devis_message', 'devis_envoye_le', 'devis_repondu_le',
                'resultat_valide_le', 'motif_annulation', 'annule_par',
            ]);
        });
    }
};
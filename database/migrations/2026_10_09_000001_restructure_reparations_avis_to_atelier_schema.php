<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aligne les tables reparations et avis sur le diagramme de classes de l'équipe :
     * une réparation concerne un vêtement (et plus tard un atelier),
     * un avis est laissé par un utilisateur sur un atelier (et plus tard un atelier).
     */
    public function up(): void
    {
        Schema::table('reparations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'titre',
                'description',
                'cout_estime',
                'date_reparation',
                'image',
            ]);

            $table->foreignId('vetement_id')->constrained()->cascadeOnDelete();

            // TODO: add foreign key to ateliers once the Atelier migration is merged
            $table->unsignedBigInteger('atelier_id')->nullable();

            $table->text('description_probleme');
            $table->decimal('devis', 10, 2)->nullable();
            $table->dateTime('date_rdv');
        });

        Schema::table('avis', function (Blueprint $table) {
            $table->dropForeign(['reparation_id']);
            $table->dropColumn('reparation_id');

            // TODO: add foreign key to ateliers once the Atelier migration is merged
            $table->unsignedBigInteger('atelier_id')->nullable();

            $table->dateTime('date')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::table('reparations', function (Blueprint $table) {
            $table->dropForeign(['vetement_id']);
            $table->dropColumn([
                'vetement_id',
                'atelier_id',
                'description_probleme',
                'devis',
                'date_rdv',
            ]);

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->decimal('cout_estime', 8, 2)->default(0);
            $table->date('date_reparation');
            $table->string('image')->nullable();
        });

        Schema::table('avis', function (Blueprint $table) {
            $table->dropColumn(['atelier_id', 'date']);

            $table->foreignId('reparation_id')->constrained()->cascadeOnDelete();
        });
    }
};
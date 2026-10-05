<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_collecte', function (Blueprint $table) {
            $table->id();
            // La table associations est livrée par le module Association (membre 2) :
            // colonne indexée sans contrainte pour l'instant, la FK sera ajoutée à l'intégration.
            $table->unsignedBigInteger('association_id')->nullable()->index();
            $table->string('nom');
            $table->string('adresse');
            $table->string('ville')->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('horaires')->nullable();
            $table->unsignedInteger('capacite');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_collecte');
    }
};

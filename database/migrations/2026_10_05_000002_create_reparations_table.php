<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained()->cascadeOnDelete();
            // Vetement n'existe pas encore comme table (voir diagramme complet) :
            // on garde vetement_id nullable sans contrainte + un titre libre pour l'affichage.
            $table->unsignedBigInteger('vetement_id')->nullable();
            $table->string('vetement_titre')->nullable();
            $table->text('description_probleme');
            $table->decimal('devis', 10, 2)->nullable();
            $table->dateTime('date_rdv')->nullable();
            $table->string('statut')->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparations');
    }
};

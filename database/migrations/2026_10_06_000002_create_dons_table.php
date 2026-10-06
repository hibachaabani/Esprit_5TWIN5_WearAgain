<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dons', function (Blueprint $table) {
            $table->id();
            // Vetement n'existe pas encore comme table (voir diagramme complet) :
            // on garde vetement_id nullable sans contrainte + un titre libre pour l'affichage,
            // comme pour reparations.vetement_id.
            $table->unsignedBigInteger('vetement_id')->nullable();
            $table->string('vetement_titre')->nullable();
            $table->foreignId('donateur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->date('date_don')->nullable();
            $table->string('mode')->default('depot');
            $table->string('statut')->default('propose');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dons');
    }
};

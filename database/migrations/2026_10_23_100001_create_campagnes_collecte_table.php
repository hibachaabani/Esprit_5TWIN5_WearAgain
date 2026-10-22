<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campagne de collecte : une association fixe un objectif de vêtements sur une période,
 * pour un de ses points (ou pour tous ses points si aucun n'est choisi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagnes_collecte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->foreignId('point_collecte_id')->nullable()->constrained('points_collecte')->nullOnDelete();
            $table->string('titre', 120);
            $table->text('description');
            $table->unsignedInteger('objectif');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->timestamps();

            $table->index(['date_debut', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagnes_collecte');
    }
};

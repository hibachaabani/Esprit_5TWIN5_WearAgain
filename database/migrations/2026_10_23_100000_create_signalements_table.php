<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signalement d'un problème sur un point de collecte (conteneur plein, abîmé…),
 * déclaré par un particulier et traité par l'association qui gère le point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_collecte_id')->constrained('points_collecte')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('message');
            $table->string('statut', 20)->default('ouvert');
            // Priorité et résumé proposés par l'assistant IA (ou le moteur local).
            $table->string('priorite', 10)->default('moyenne');
            $table->string('resume_ia')->nullable();
            $table->text('reponse')->nullable();
            $table->timestamp('traite_le')->nullable();
            $table->timestamps();

            $table->index(['point_collecte_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};

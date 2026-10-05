<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            // La table vetements est livrée par le module Vetement (membre 1) :
            // colonne indexée sans contrainte + titre libre en attendant l'intégration.
            $table->unsignedBigInteger('vetement_id')->nullable()->index();
            $table->string('vetement_titre');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('point_collecte_id')->constrained('points_collecte')->cascadeOnDelete();
            $table->date('date_depot');
            $table->enum('statut', ['en_attente', 'recu', 'trie', 'annule'])->default('en_attente');
            $table->text('commentaire')->nullable();
            $table->timestamp('recu_le')->nullable();
            $table->timestamp('trie_le')->nullable();
            $table->timestamp('annule_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depots');
    }
};

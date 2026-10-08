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
            // Vêtement publié par le particulier (module Vetement, membre 1), facultatif :
            // le titre est recopié pour garder l'historique même si l'annonce disparaît.
            $table->foreignId('vetement_id')->nullable()->constrained()->nullOnDelete();
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

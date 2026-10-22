<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Journal des étapes d'une transformation : chaque changement de statut, son
| auteur et un commentaire facultatif — fournit la timeline côté particulier
| et atelier (même principe que reparation_suivis).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transformation_suivis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transformation_id')->constrained('transformations')->cascadeOnDelete();
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ancien_statut')->nullable();
            $table->string('nouveau_statut');
            $table->text('commentaire')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transformation_suivis');
    }
};
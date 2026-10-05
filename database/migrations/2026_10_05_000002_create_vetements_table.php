<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vetements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('categorie_id')->constrained('categories')->cascadeOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('taille')->nullable();       // XS, S, M, L, XL, XXL, ou pointure
            $table->string('matiere')->nullable();      // coton, lin, laine…
            $table->string('couleur')->nullable();      // libellé ex: "bleu marine"
            $table->string('etat');                     // neuf, très_bon, bon, satisfaisant
            $table->string('photo')->nullable();        // chemin vers l'image stockée
            $table->string('statut')->default('disponible'); // disponible, vendu, échangé, archivé
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vetements');
    }
};

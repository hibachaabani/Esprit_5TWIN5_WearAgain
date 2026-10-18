<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('besoins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('categorie');
            $table->unsignedInteger('quantite');
            $table->string('urgence')->default('normale');
            $table->date('date_limite')->nullable();
            $table->string('statut')->default('ouvert');
            $table->timestamps();

            $table->index(['statut', 'urgence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('besoins');
    }
};

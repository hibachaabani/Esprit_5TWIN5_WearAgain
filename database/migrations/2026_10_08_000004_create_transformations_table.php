<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transformations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vetement_id')->constrained('vetements')->cascadeOnDelete();
            $table->foreignId('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->string('type');
            $table->text('description')->nullable();
            $table->decimal('prix', 8, 2)->default(0);
            $table->string('image_resultat')->nullable();
            $table->string('statut')->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transformations');
    }
};

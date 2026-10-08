<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vetement_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vetement_id')->constrained('vetements')->cascadeOnDelete();
            $table->string('chemin');        // chemin dans storage/public
            $table->unsignedTinyInteger('ordre')->default(0); // 0 = photo principale
            $table->timestamps();
        });

        // La colonne "photo" sur vetements devient redondante — on la garde
        // pour compatibilité mais on s'appuie sur vetement_photos désormais.
    }

    public function down(): void
    {
        Schema::dropIfExists('vetement_photos');
    }
};

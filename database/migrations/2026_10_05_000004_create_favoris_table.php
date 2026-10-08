<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favoris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vetement_id')->constrained('vetements')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'vetement_id']); // un seul favori par user/article
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoris');
    }
};

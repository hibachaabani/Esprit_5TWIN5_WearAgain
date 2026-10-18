<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorie_marque', function (Blueprint $table) {
            $table->foreignId('categorie_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('marque_id')->constrained('marques')->cascadeOnDelete();
            $table->primary(['categorie_id', 'marque_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorie_marque');
    }
};

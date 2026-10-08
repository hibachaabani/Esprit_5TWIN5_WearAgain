<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_collecte', function (Blueprint $table) {
            $table->id();
            // Association responsable (module Association, membre 2).
            $table->foreignId('association_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nom');
            $table->string('adresse');
            $table->string('ville')->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('horaires')->nullable();
            $table->unsignedInteger('capacite');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_collecte');
    }
};

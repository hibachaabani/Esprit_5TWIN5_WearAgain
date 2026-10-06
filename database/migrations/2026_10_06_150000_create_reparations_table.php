<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->string('statut')->default('en_attente');
            $table->decimal('cout_estime', 8, 2)->default(0);
            $table->date('date_reparation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparations');
    }
};

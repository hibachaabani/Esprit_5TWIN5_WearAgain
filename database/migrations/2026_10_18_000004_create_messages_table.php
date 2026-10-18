<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fil de discussion d'un don entre le donateur et l'association.
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_id')->constrained()->cascadeOnDelete();
            // Vide pour un événement automatique (« Don accepté… »).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('message');
            $table->text('contenu');
            $table->timestamp('lu_le')->nullable();
            $table->timestamps();

            $table->index(['don_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catégories : ordre d'affichage + visibilité
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('ordre')->default(0)->after('icone');
            $table->boolean('active')->default(true)->after('ordre');
        });

        // Vêtements : signalement
        Schema::table('vetements', function (Blueprint $table) {
            $table->boolean('signale')->default(false)->after('statut');
            $table->text('raison_signalement')->nullable()->after('signale');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['ordre', 'active']);
        });

        Schema::table('vetements', function (Blueprint $table) {
            $table->dropColumn(['signale', 'raison_signalement']);
        });
    }
};

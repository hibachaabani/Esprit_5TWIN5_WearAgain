<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dons', function (Blueprint $table) {
            // Donateur : adresse où l'association vient chercher le vêtement (mode « livraison »).
            $table->string('adresse_retrait')->nullable()->after('mode');
            // Association : rendez-vous de remise fixé à l'acceptation, ou motif du refus.
            $table->dateTime('date_remise')->nullable()->after('adresse_retrait');
            $table->string('lieu_remise')->nullable()->after('date_remise');
            $table->text('consigne')->nullable()->after('lieu_remise');
            $table->string('motif_refus')->nullable()->after('consigne');
            // Horodatage des étapes pour la frise du don.
            $table->timestamp('accepte_le')->nullable()->after('motif_refus');
            $table->timestamp('cloture_le')->nullable()->after('accepte_le');
        });
    }

    public function down(): void
    {
        Schema::table('dons', function (Blueprint $table) {
            $table->dropColumn(['adresse_retrait', 'date_remise', 'lieu_remise', 'consigne', 'motif_refus', 'accepte_le', 'cloture_le']);
        });
    }
};

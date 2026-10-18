<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dons', function (Blueprint $table) {
            // Un don peut répondre à un besoin publié par l'association (facultatif).
            $table->foreignId('besoin_id')->nullable()->after('association_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('besoin_id');
        });
    }
};

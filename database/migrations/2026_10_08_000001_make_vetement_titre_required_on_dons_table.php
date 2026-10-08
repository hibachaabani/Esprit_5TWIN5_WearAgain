<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dons', function (Blueprint $table) {
            $table->string('vetement_titre')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('dons', function (Blueprint $table) {
            $table->string('vetement_titre')->nullable()->change();
        });
    }
};

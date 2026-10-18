<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->foreignId('marque_id')->nullable()->constrained('marques')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->dropForeign(['marque_id']);
            $table->dropColumn('marque_id');
        });
    }
};

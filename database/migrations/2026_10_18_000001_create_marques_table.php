<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marques', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100)->unique();
            $table->string('pays_origine', 100)->nullable();
            $table->string('logo')->nullable();
            $table->string('site_web', 255)->nullable();
            $table->boolean('est_ethique')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marques');
    }
};

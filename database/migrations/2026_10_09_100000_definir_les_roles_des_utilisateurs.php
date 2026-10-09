<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Rôles du projet (guide des entités) : particulier, atelier, association, admin.
| Remplace l'ancien rôle « member » et déduit le rôle des comptes qui gèrent
| déjà une association ou un atelier.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('particulier')->change();
        });

        DB::table('users')->where('role', 'member')->update(['role' => 'particulier']);

        DB::table('users')
            ->where('role', '!=', 'admin')
            ->whereIn('id', DB::table('associations')->whereNotNull('user_id')->select('user_id'))
            ->update(['role' => 'association']);

        DB::table('users')
            ->where('role', '!=', 'admin')
            ->whereIn('id', DB::table('ateliers')->select('user_id'))
            ->update(['role' => 'atelier']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', '!=', 'admin')->update(['role' => 'member']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->change();
        });
    }
};

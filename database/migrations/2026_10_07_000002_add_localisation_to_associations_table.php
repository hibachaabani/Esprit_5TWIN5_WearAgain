<?php

use App\Models\Association;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('associations', function (Blueprint $table) {
            $table->json('categories')->nullable()->after('mission');
            $table->decimal('latitude', 10, 7)->nullable()->after('ville');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['latitude', 'longitude']);
        });

        // Positionne les associations existantes au centre de leur ville quand elle est connue.
        DB::table('associations')->whereNotNull('ville')->orderBy('id')->each(function ($row) {
            if ($coords = Association::coordonneesVille($row->ville)) {
                DB::table('associations')->where('id', $row->id)->update([
                    'latitude' => $coords[0],
                    'longitude' => $coords[1],
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('associations', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['categories', 'latitude', 'longitude']);
        });
    }
};

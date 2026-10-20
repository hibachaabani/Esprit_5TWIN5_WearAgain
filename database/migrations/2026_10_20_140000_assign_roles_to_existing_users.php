<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $modelType = (new User)->getMorphClass();

        DB::table('users')->orderBy('id')->pluck('role', 'id')->each(function ($role, $userId) use ($modelType) {
            if (! $role) {
                return;
            }

            $roleModel = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

            DB::table('model_has_roles')->updateOrInsert([
                'role_id' => $roleModel->id,
                'model_type' => $modelType,
                'model_id' => $userId,
            ]);
        });
    }

    public function down(): void
    {
        DB::table('model_has_roles')->delete();
    }
};

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Crée les rôles de l'application (Spatie Permission) et synchronise
     * chaque utilisateur existant à partir de la colonne users.role.
     * Idempotent : peut être relancé à tout moment.
     */
    public function run(): void
    {
        foreach (array_keys(User::ROLES) as $nom) {
            Role::firstOrCreate(['name' => $nom, 'guard_name' => 'web']);
        }

        foreach (User::orderBy('id')->get() as $user) {
            if (! $user->role) {
                continue;
            }

            $role = Role::firstOrCreate(['name' => $user->role, 'guard_name' => 'web']);
            $user->syncRoles([$role]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

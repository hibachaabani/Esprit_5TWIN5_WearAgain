<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /** Compte de démonstration administrateur (mot de passe : Wearagain2026). */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@wearagain.tn'],
            ['name' => 'Admin WearAgain', 'password' => 'Wearagain2026'],
        );

        // Idempotent : garantit le rôle "admin" même si le compte existait déjà.
        if (! $admin->isAdmin()) {
            $admin->forceFill(['role' => 'admin'])->save();
        }
    }
}

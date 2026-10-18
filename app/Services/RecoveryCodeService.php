<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RecoveryCodeService
{
    public const COUNT = 8;

    /**
     * Génère COUNT codes de récupération lisibles (format XXXX-XXXX).
     * Retourne un tableau de codes en clair (à afficher une seule fois).
     */
    public function generate(): array
    {
        $codes = [];
        for ($i = 0; $i < self::COUNT; $i++) {
            $codes[] = strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        }
        return $codes;
    }

    /**
     * Hache les codes en clair pour le stockage.
     * Stocke aussi un flag "used" par code.
     */
    public function hash(array $plainCodes): array
    {
        return array_map(fn ($code) => [
            'code' => Hash::make($code),
            'used' => false,
        ], $plainCodes);
    }

    /**
     * Vérifie si un code en clair correspond à un code haché non utilisé.
     * Retourne l'index du code trouvé, ou null si aucun ne correspond.
     */
    public function findValid(string $plainCode, array $hashedCodes): ?int
    {
        foreach ($hashedCodes as $index => $entry) {
            if (! $entry['used'] && Hash::check($plainCode, $entry['code'])) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Marque un code comme utilisé et retourne le tableau mis à jour.
     */
    public function markUsed(array $hashedCodes, int $index): array
    {
        $hashedCodes[$index]['used'] = true;
        return $hashedCodes;
    }
}

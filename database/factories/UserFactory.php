<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /** Prénoms et noms tunisiens, différents de ceux des comptes de démo (Ahmed Trabelsi, Sana Ben Ali…). */
    private const PRENOMS = ['Amira', 'Youssef', 'Salma', 'Mehdi', 'Ines', 'Karim', 'Rania', 'Omar', 'Nour', 'Hamza', 'Yasmine', 'Sami'];

    private const NOMS = ['Gharbi', 'Jaziri', 'Mansouri', 'Ayari', 'Hammami', 'Bouazizi', 'Khelifi', 'Sassi', 'Dridi', 'Mejri'];

    private static int $numero = 0;

    /** Un nom différent à chaque utilisateur généré : les tests comparent parfois les noms affichés. */
    private function nomUnique(): string
    {
        $n = self::$numero++;
        $tour = intdiv($n, count(self::PRENOMS));
        $nom = self::PRENOMS[$n % count(self::PRENOMS)].' '.self::NOMS[$tour % count(self::NOMS)];

        return $tour < count(self::NOMS) ? $nom : $nom.' '.(intdiv($tour, count(self::NOMS)) + 1);
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->nomUnique(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'particulier',
            'remember_token' => Str::random(10),
        ];
    }

    /** Compte qui gère une association. */
    public function association(): static
    {
        return $this->state(['role' => 'association']);
    }

    /** Compte qui gère un atelier. */
    public function atelier(): static
    {
        return $this->state(['role' => 'atelier']);
    }

    /** Administrateur (back-office). */
    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

<?php

namespace App\Services\Messagerie;

use App\Models\Don;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Une conversation : tous les dons entre un donateur et une association, réunis en un seul fil.
 * Un donateur qui fait trois dons à la même association n'a qu'une discussion avec elle.
 */
final class Conversation
{
    /** @param  Collection<int, Don>  $dons  dons de la paire, du plus récent au plus ancien */
    public function __construct(public readonly Collection $dons) {}

    /** Identifiant de la paire donateur ↔ association d'un don (« 12-5 »). */
    public static function cle(Don $don): string
    {
        return $don->donateur_id.'-'.$don->association_id;
    }

    public function identifiant(): string
    {
        return self::cle($this->dons->first());
    }

    public function contient(?Don $don): bool
    {
        return $don !== null && self::cle($don) === $this->identifiant();
    }

    public function interlocuteurPour(?User $user): string
    {
        return $this->dons->first()->interlocuteurPour($user);
    }

    /** Les dons en cours (proposés ou acceptés) : on peut encore écrire à leur sujet. */
    public function donsEnCours(): Collection
    {
        return $this->dons->filter->filOuvert()->values();
    }

    /**
     * Les dons en cours tels que proposés dans « À propos de » ; la page compare cette liste
     * à chaque actualisation pour se recharger quand un don avance.
     *
     * @return array<int, array{id: int, titre: string}>
     */
    public function sujets(): array
    {
        return $this->donsEnCours()
            ->map(fn (Don $don) => ['id' => $don->id, 'titre' => $don->vetement_titre.' · '.Don::LIBELLES_STATUT[$don->statut]])
            ->all();
    }

    public function estOuverte(): bool
    {
        return $this->donsEnCours()->isNotEmpty();
    }

    /** Le don qui sert d'adresse à la conversation : le plus récent en cours, sinon le plus récent. */
    public function donPrincipal(): Don
    {
        return $this->donsEnCours()->first() ?? $this->dons->first();
    }

    /** Avec plusieurs dons, chaque message rappelle le vêtement dont il parle. */
    public function plusieursDons(): bool
    {
        return $this->dons->count() > 1;
    }

    public function nonLus(): int
    {
        return (int) $this->dons->sum('non_lus_count');
    }

    public function dernierMessage(): ?Message
    {
        return $this->dons->pluck('dernierMessage')->filter()->sortByDesc('id')->first();
    }

    public function derniereActivite(): Carbon
    {
        return $this->dernierMessage()?->created_at ?? $this->dons->max('created_at');
    }

    /** « Parka 10 ans · Proposé » pour un seul don ; « 3 dons · Parka 10 ans, Body 3 mois… » sinon. */
    public function resume(): string
    {
        if (! $this->plusieursDons()) {
            $don = $this->dons->first();

            return $don->vetement_titre.' · '.Don::LIBELLES_STATUT[$don->statut];
        }

        $titres = $this->donsEnCours()->concat($this->dons->reject->filOuvert())->pluck('vetement_titre')->unique();

        return $this->dons->count().' dons · '.Str::limit($titres->join(', '), 60);
    }

    /** Recherche sur le nom de l'autre participant et les vêtements donnés, sans tenir compte des accents. */
    public function correspond(string $terme, User $user): bool
    {
        $normaliser = fn (string $texte) => Str::lower(Str::ascii($texte));

        return $this->dons->pluck('vetement_titre')->push($this->interlocuteurPour($user))
            ->contains(fn ($texte) => str_contains($normaliser($texte), $normaliser($terme)));
    }
}

<?php

namespace App\Services\Messagerie;

use App\Models\Don;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Construit les conversations de la messagerie (une par donateur et association)
 * et leur fil : les messages de tous les dons de la paire, dans l'ordre où ils ont été écrits.
 */
class Conversations
{
    /**
     * Les conversations de cet utilisateur, la plus récemment active en premier.
     *
     * @return Collection<int, Conversation>
     */
    public function de(User $user, ?string $recherche = null): Collection
    {
        return Don::concernant($user)
            ->with(['association.user', 'donateur', 'dernierMessage'])
            ->withCount(['messages as non_lus_count' => fn ($q) => $q->nonLusPour($user)])
            ->latest()
            ->latest('id')
            ->limit(500)
            ->get()
            ->groupBy(fn (Don $don) => Conversation::cle($don))
            ->map(fn (Collection $dons) => new Conversation($dons->values()))
            ->when($recherche, fn ($liste, $terme) => $liste->filter(fn (Conversation $c) => $c->correspond($terme, $user)))
            ->sortByDesc(fn (Conversation $c) => $c->derniereActivite()->getTimestamp())
            ->values();
    }

    /** La conversation dont fait partie ce don : tous les dons entre ce donateur et cette association. */
    public function autourDe(Don $don, bool $avecMessages = false): Conversation
    {
        $dons = Don::where('donateur_id', $don->donateur_id)
            ->where('association_id', $don->association_id)
            ->with(array_merge(['donateur', 'association.user', 'besoin'], $avecMessages ? ['messages.auteur'] : []))
            ->latest()
            ->latest('id')
            ->get();

        // Chaque message connaît son don sans nouvelle requête (titre affiché, droits de modification).
        if ($avecMessages) {
            $dons->each(fn (Don $d) => $d->messages->each->setRelation('don', $d));
        }

        return new Conversation($dons);
    }

    /** Le fil complet de la conversation, avec le message joint à chaque proposition. */
    public function fil(Conversation $conversation): Collection
    {
        $messages = $conversation->dons->flatMap->messages->sortBy('id')->values();
        $fil = $this->avecMessagesJoints($messages, $conversation);

        // Un don sans aucun événement (données anciennes) : son message joint se place à sa date.
        foreach ($conversation->dons as $don) {
            if ($don->message && $don->messages->isEmpty()) {
                $position = $fil->search(fn (Message $m) => $m->created_at->gt($don->created_at));
                $fil->splice($position === false ? $fil->count() : $position, 0, [$this->messageJoint($don)]);
            }
        }

        return $fil;
    }

    /**
     * Les messages écrits après celui-ci, pour l'actualisation de la page.
     *
     * @return Collection<int, Message>
     */
    public function nouveaux(Conversation $conversation, int $apres): Collection
    {
        $dons = $conversation->dons->keyBy('id');
        $messages = Message::whereIn('don_id', $dons->keys())
            ->where('id', '>', $apres)
            ->with('auteur')
            ->orderBy('id')
            ->get()
            ->each(fn (Message $m) => $m->setRelation('don', $dons[$m->don_id]));

        return $this->avecMessagesJoints($messages, $conversation);
    }

    /** Le don du message affiché juste avant celui-ci : un changement de don affiche « À propos de … ». */
    public function donPrecedent(Conversation $conversation, int $avant): ?int
    {
        return Message::whereIn('don_id', $conversation->dons->pluck('id'))
            ->where('id', '<', $avant)
            ->orderByDesc('id')
            ->value('don_id');
    }

    public function marquerLue(Conversation $conversation, User $user): void
    {
        // toBase() : la lecture ne touche pas updated_at (sinon le message paraîtrait « modifié »).
        Message::whereIn('don_id', $conversation->dons->pluck('id'))->nonLusPour($user)->toBase()->update(['lu_le' => now()]);
    }

    /** Ajoute, juste après le premier message de chaque don (l'événement « Don proposé »), le mot joint par le donateur. */
    private function avecMessagesJoints(Collection $messages, Conversation $conversation): Collection
    {
        $premiers = Message::whereIn('don_id', $conversation->dons->pluck('id'))
            ->selectRaw('don_id, min(id) as premier')
            ->groupBy('don_id')
            ->pluck('premier', 'don_id');

        return $messages->flatMap(fn (Message $message) => $message->don->message && (int) $premiers[$message->don_id] === $message->id
            ? [$message, $this->messageJoint($message->don)]
            : [$message])->values();
    }

    private function messageJoint(Don $don): Message
    {
        $message = new Message(['user_id' => $don->donateur_id, 'type' => 'message', 'contenu' => $don->message]);
        $message->setAttribute('don_id', $don->id)
            ->setAttribute('created_at', $don->created_at)
            ->setAttribute('updated_at', $don->created_at)
            ->setRelation('don', $don)
            ->setRelation('auteur', $don->donateur);

        return $message;
    }
}

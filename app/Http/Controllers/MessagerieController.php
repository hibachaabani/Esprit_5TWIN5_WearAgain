<?php

namespace App\Http\Controllers;

use App\Models\Don;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Messagerie façon « Messenger » : une conversation par don, entre le donateur et l'association.
 * La page se met à jour seule (interrogation régulière) et le menu affiche les messages non lus.
 */
class MessagerieController extends Controller
{
    /** Liste des conversations, sans conversation ouverte. */
    public function index(Request $request): View
    {
        return view('messagerie.index', [
            'conversations' => $this->conversations($request->user(), $request->query('q')),
            'don' => null,
        ]);
    }

    /** Conversation d'un don, à côté de la liste. */
    public function show(Request $request, Don $don): View
    {
        abort_unless($don->estVisiblePar($request->user()), 403);

        $don->load(['donateur', 'association', 'besoin', 'messages.auteur']);
        $don->marquerLuPar($request->user());

        return view('messagerie.index', [
            'conversations' => $this->conversations($request->user(), $request->query('q')),
            'don' => $don,
        ]);
    }

    /** Nouveaux messages d'une conversation depuis le dernier affiché (appelé toutes les quelques secondes). */
    public function nouveaux(Request $request, Don $don): JsonResponse
    {
        abort_unless($don->estVisiblePar($request->user()), 403);

        $messages = $don->messages()->with('auteur')->where('id', '>', (int) $request->query('apres'))->get();
        $don->marquerLuPar($request->user());

        return response()->json([
            'html' => $messages->map(fn (Message $message) => view('messagerie._bulle', ['message' => $message, 'don' => $don])->render())->join(''),
            'dernier_id' => $messages->last()?->id,
            'fil_ouvert' => $don->fresh()->filOuvert(),
        ]);
    }

    /** Compteur global pour le badge du menu et la notification « Nouveau message de … ». */
    public function nonLus(Request $request): JsonResponse
    {
        $user = $request->user();
        $dernier = Message::nonLusPour($user)
            ->whereHas('don', fn ($q) => $q->concernant($user))
            ->with(['auteur', 'don.association', 'don.donateur'])
            ->latest('id')
            ->first();

        return response()->json([
            'total' => $user->messagesNonLus(),
            'dernier' => $dernier ? [
                'id' => $dernier->id,
                'auteur' => $dernier->don->interlocuteurPour($user),
                'extrait' => str($dernier->contenu)->limit(80)->toString(),
                'url' => route('messagerie.show', $dernier->don_id),
            ] : null,
        ]);
    }

    /**
     * Conversations de l'utilisateur, la plus récemment active en premier.
     *
     * @return Collection<int, Don>
     */
    private function conversations(User $user, ?string $recherche): Collection
    {
        return Don::concernant($user)
            ->with(['association', 'donateur', 'dernierMessage'])
            ->withCount(['messages as non_lus_count' => fn ($q) => $q->nonLusPour($user)])
            ->withMax('messages', 'created_at')
            ->when($recherche, fn ($q, $terme) => $q->where(fn ($w) => $w
                ->where('vetement_titre', 'like', "%{$terme}%")
                ->orWhereHas('donateur', fn ($d) => $d->where('name', 'like', "%{$terme}%"))
                ->orWhereHas('association', fn ($a) => $a->where('nom', 'like', "%{$terme}%"))))
            ->orderByRaw('coalesce(messages_max_created_at, dons.created_at) desc')
            ->limit(100)
            ->get();
    }
}

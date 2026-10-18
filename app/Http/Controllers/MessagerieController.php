<?php

namespace App\Http\Controllers;

use App\Models\Don;
use App\Models\Message;
use App\Services\Messagerie\Conversation;
use App\Services\Messagerie\Conversations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Messagerie façon « Messenger » : une conversation par donateur et association, qui réunit
 * les messages de tous leurs dons. La page se met à jour seule (interrogation régulière).
 */
class MessagerieController extends Controller
{
    public function __construct(private readonly Conversations $conversations) {}

    /** Liste des conversations, sans conversation ouverte. */
    public function index(Request $request): View
    {
        return view('messagerie.index', [
            'conversations' => $this->conversations->de($request->user(), $request->query('q')),
            'conversation' => null,
            'don' => null,
        ]);
    }

    /**
     * La conversation dont fait partie ce don, à côté de la liste. Le don de l'adresse est proposé
     * comme sujet du prochain message s'il est encore en cours.
     */
    public function show(Request $request, Don $don): View
    {
        abort_unless($don->estVisiblePar($request->user()), 403);

        $conversation = $this->conversations->autourDe($don, avecMessages: true);
        $this->conversations->marquerLue($conversation, $request->user());

        return view('messagerie.index', [
            'conversations' => $this->conversations->de($request->user(), $request->query('q')),
            'conversation' => $conversation,
            'fil' => $this->conversations->fil($conversation),
            'don' => $conversation->dons->firstWhere('id', $don->id),
        ]);
    }

    /** Nouveaux messages de la conversation depuis le dernier affiché (appelé toutes les quelques secondes). */
    public function nouveaux(Request $request, Don $don): JsonResponse
    {
        abort_unless($don->estVisiblePar($request->user()), 403);

        $apres = (int) $request->query('apres');
        $conversation = $this->conversations->autourDe($don);
        $messages = $this->conversations->nouveaux($conversation, $apres);
        $this->conversations->marquerLue($conversation, $request->user());

        return response()->json([
            'html' => $messages->isEmpty() ? '' : view('messagerie._fil', [
                'messages' => $messages,
                'conversation' => $conversation,
                'precedent' => $this->conversations->donPrecedent($conversation, $apres + 1),
            ])->render(),
            'dernier_id' => $messages->max('id'),
            'fil_ouvert' => $conversation->estOuverte(),
            // Les dons sur lesquels on peut encore écrire : la liste « À propos de » suit leur évolution.
            'sujets' => $conversation->sujets(),
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
                'conversation' => Conversation::cle($dernier->don),
            ] : null,
        ]);
    }
}

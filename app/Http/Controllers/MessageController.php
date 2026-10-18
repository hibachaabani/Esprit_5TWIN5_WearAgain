<?php

namespace App\Http\Controllers;

use App\Models\Don;
use App\Models\Message;
use App\Services\Messagerie\Conversations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Messages entre un donateur et une association : chaque message porte sur l'un de leurs dons en cours.
 * Chacun peut modifier ou supprimer ses propres messages.
 */
class MessageController extends Controller
{
    public function store(Request $request, Don $don, Conversations $conversations): RedirectResponse|JsonResponse
    {
        abort_unless($don->estVisiblePar($request->user()), 403);

        // Depuis la messagerie, le message peut porter sur un autre don en cours avec la même personne (« À propos de »).
        if ($request->filled('don_id') && (int) $request->input('don_id') !== $don->id) {
            $don = Don::whereKey((int) $request->input('don_id'))
                ->where('donateur_id', $don->donateur_id)
                ->where('association_id', $don->association_id)
                ->firstOrFail();
        }

        if (! $don->filOuvert()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Ce don est terminé : la discussion est fermée.'], 422)
                : back()->with('erreur', 'Ce don est terminé : la discussion est fermée.');
        }

        $message = $don->messages()->create($this->valider($request) + [
            'user_id' => $request->user()->id,
            'type' => 'message',
        ]);

        // Envoi depuis la messagerie (sans rechargement) : on renvoie la bulle à ajouter au fil.
        if ($request->expectsJson()) {
            $conversation = $conversations->autourDe($don);
            $message->load('auteur')->setRelation('don', $conversation->dons->firstWhere('id', $don->id));

            return response()->json([
                'html' => view('messagerie._fil', [
                    'messages' => collect([$message]),
                    'conversation' => $conversation,
                    'precedent' => $conversations->donPrecedent($conversation, $message->id),
                ])->render(),
                'id' => $message->id,
            ], 201);
        }

        return redirect()->back(fallback: route('dons.show', $don).'#fil')->with('status', 'Message envoyé.');
    }

    public function update(Request $request, Message $message): RedirectResponse
    {
        abort_unless($message->peutEtreModifiePar($request->user()), 403);

        $message->update($this->valider($request));

        return redirect()->back(fallback: route('dons.show', $message->don_id))->with('status', 'Message modifié.');
    }

    public function destroy(Request $request, Message $message): RedirectResponse
    {
        abort_unless($message->peutEtreModifiePar($request->user()), 403);

        $message->delete();

        return redirect()->back(fallback: route('dons.show', $message->don_id))->with('status', 'Message supprimé.');
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'contenu' => ['required', 'string', 'min:2', 'max:1000'],
        ], [
            'contenu.required' => 'Le message est vide.',
            'contenu.min' => 'Le message doit contenir au moins :min caractères.',
            'contenu.max' => 'Le message ne doit pas dépasser :max caractères.',
        ]);
    }
}

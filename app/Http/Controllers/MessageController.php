<?php

namespace App\Http\Controllers;

use App\Models\Don;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Fil de discussion d'un don : le donateur et l'association s'écrivent tant que le don est en cours.
 * Chacun peut modifier ou supprimer ses propres messages.
 */
class MessageController extends Controller
{
    public function store(Request $request, Don $don): RedirectResponse|JsonResponse
    {
        abort_unless($don->estVisiblePar($request->user()), 403);

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
            return response()->json([
                'html' => view('messagerie._bulle', ['message' => $message->load('auteur'), 'don' => $don])->render(),
                'id' => $message->id,
            ], 201);
        }

        return redirect()->to(route('dons.show', $don).'#fil')->with('status', 'Message envoyé.');
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

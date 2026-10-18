<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cloche de notifications : étapes des dons (proposé, accepté, refusé, remis, annulé)
 * et nouveaux messages. js/notifications.js interroge « etat » pour le badge, le son et la bulle.
 */
class NotificationController extends Controller
{
    /** Toutes les notifications du compte. */
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    /** Ouvre une notification : elle passe en lue, puis on va sur le don concerné. */
    public function ouvrir(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('espace'));
    }

    public function toutLire(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Toutes les notifications sont marquées comme lues.');
    }

    /** Badges, dernière notification et dernier message non lus, et le contenu de la cloche. */
    public function etat(Request $request): JsonResponse
    {
        $user = $request->user();
        $derniere = $user->unreadNotifications()->first();
        $message = Message::nonLusPour($user)
            ->whereHas('don', fn ($q) => $q->concernant($user))
            ->with(['don.association', 'don.donateur'])
            ->latest('id')
            ->first();

        return response()->json([
            'notifications' => $user->unreadNotifications()->count(),
            'messages' => $user->messagesNonLus(),
            'derniere' => $derniere ? [
                'id' => $derniere->id,
                'icone' => $derniere->data['icone'] ?? '🔔',
                'titre' => $derniere->data['titre'] ?? 'Notification',
                'texte' => $derniere->data['texte'] ?? '',
                'url' => route('notifications.ouvrir', $derniere->id),
            ] : null,
            'message' => $message ? [
                'id' => $message->id,
                'icone' => '💬',
                'titre' => 'Nouveau message de '.$message->don->interlocuteurPour($user),
                'texte' => str($message->contenu)->limit(80)->toString(),
                'url' => route('messagerie.show', $message->don_id),
            ] : null,
            'liste' => view('notifications._liste', ['notifications' => $user->notifications()->take(6)->get()])->render(),
        ]);
    }
}

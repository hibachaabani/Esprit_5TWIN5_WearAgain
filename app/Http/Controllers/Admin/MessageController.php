<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    /** Back-office : derniers messages échangés entre donateurs et associations (modération). */
    public function index(Request $request): View
    {
        $messages = Message::with(['auteur', 'don.association'])
            ->where('type', 'message')
            ->when($request->query('q'), fn ($q, $terme) => $q->where('contenu', 'like', "%{$terme}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.messages.index', compact('messages'));
    }

    /** Modération : supprime un message inapproprié. */
    public function destroy(Message $message): RedirectResponse
    {
        $message->delete();

        return back()->with('status', 'Message supprimé.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Atelier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AtelierController extends Controller
{
    public function index(Request $request): View
    {
        $query = Atelier::withCount('reparations')->latest();

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('nom', 'like', "%{$q}%")
                    ->orWhere('ville', 'like', "%{$q}%")
                    ->orWhere('specialites', 'like', "%{$q}%");
            });
        }

        $ateliers = $query->paginate(9)->withQueryString();

        return view('ateliers.index', compact('ateliers'));
    }

    public function create(): View
    {
        return view('ateliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'specialites' => ['nullable', 'string', 'max:255'],
            'horaires' => ['nullable', 'string', 'max:255'],
        ]);

        $atelier = Atelier::create($data);

        return redirect()
            ->route('ateliers.show', $atelier)
            ->with('status', 'Atelier créé avec succès.');
    }

    public function show(Atelier $atelier): View
    {
        $atelier->load(['reparations' => fn ($q) => $q->latest()->limit(5)]);

        return view('ateliers.show', compact('atelier'));
    }

    public function edit(Atelier $atelier): View
    {
        return view('ateliers.edit', compact('atelier'));
    }

    public function update(Request $request, Atelier $atelier): RedirectResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'specialites' => ['nullable', 'string', 'max:255'],
            'horaires' => ['nullable', 'string', 'max:255'],
        ]);

        $atelier->update($data);

        return redirect()
            ->route('ateliers.show', $atelier)
            ->with('status', 'Atelier mis à jour.');
    }

    public function destroy(Atelier $atelier): RedirectResponse
    {
        $atelier->delete();

        return redirect()
            ->route('ateliers.index')
            ->with('status', 'Atelier supprimé.');
    }
}

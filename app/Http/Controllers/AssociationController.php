<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssociationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Association::withCount('dons')->latest();

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('nom', 'like', "%{$q}%")
                    ->orWhere('ville', 'like', "%{$q}%");
            });
        }

        $associations = $query->paginate(9)->withQueryString();

        return view('associations.index', compact('associations'));
    }

    public function adminIndex(): View
    {
        $associations = Association::withCount('dons')->latest()->paginate(12);

        return view('admin.associations.index', compact('associations'));
    }

    public function create(): View
    {
        $users = User::orderBy('name')->get();

        return view('associations.create', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $association = Association::create($data);

        return redirect()
            ->route('associations.show', $association)
            ->with('status', 'Association créée avec succès.');
    }

    public function show(Association $association): View
    {
        $association->load(['user', 'dons' => fn ($q) => $q->latest()->limit(5)->with('donateur')]);

        return view('associations.show', compact('association'));
    }

    public function edit(Association $association): View
    {
        $users = User::orderBy('name')->get();

        return view('associations.edit', compact('association', 'users'));
    }

    public function update(Request $request, Association $association): RedirectResponse
    {
        $data = $this->validated($request, $association);

        $association->update($data);

        return redirect()
            ->route('associations.show', $association)
            ->with('status', 'Association mise à jour.');
    }

    public function destroy(Association $association): RedirectResponse
    {
        $association->delete();

        return redirect()
            ->route('associations.index')
            ->with('status', 'Association supprimée.');
    }

    public function verifier(Association $association): RedirectResponse
    {
        $association->update(['verifiee' => ! $association->verifiee]);

        return redirect()
            ->route('admin.associations.index')
            ->with('status', $association->verifiee
                ? "{$association->nom} est maintenant vérifiée."
                : "Vérification retirée pour {$association->nom}.");
    }

    private function validated(Request $request, ?Association $association = null): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id', 'unique:associations,user_id,'.($association?->id).',id'],
            'nom' => ['required', 'string', 'max:255'],
            'mission' => ['nullable', 'string'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'numero_agrement' => ['required', 'string', 'max:255', 'unique:associations,numero_agrement,'.($association?->id)],
        ]);
    }
}

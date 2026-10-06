<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonController extends Controller
{
    public function index(Request $request): View
    {
        $query = Don::with(['donateur', 'association'])->latest();

        if ($statut = $request->query('statut')) {
            $query->where('statut', $statut);
        }

        $dons = $query->paginate(9)->withQueryString();

        return view('dons.index', compact('dons'));
    }

    public function adminIndex(Request $request): View
    {
        $query = Don::with(['donateur', 'association'])->latest();

        if ($statut = $request->query('statut')) {
            $query->where('statut', $statut);
        }

        $dons = $query->paginate(15)->withQueryString();

        return view('admin.dons.index', compact('dons'));
    }

    public function create(Request $request): View
    {
        $donateurs = User::orderBy('name')->get();
        $associations = Association::orderBy('nom')->get();
        $associationId = $request->query('association_id');

        return view('dons.create', compact('donateurs', 'associations', 'associationId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['statut'] = 'propose';

        $don = Don::create($data);

        return redirect()
            ->route('dons.show', $don)
            ->with('status', 'Don proposé avec succès.');
    }

    public function show(Don $don): View
    {
        $don->load(['donateur', 'association']);

        return view('dons.show', compact('don'));
    }

    public function edit(Don $don): View
    {
        $donateurs = User::orderBy('name')->get();
        $associations = Association::orderBy('nom')->get();

        return view('dons.edit', compact('don', 'donateurs', 'associations'));
    }

    public function update(Request $request, Don $don): RedirectResponse
    {
        $data = $this->validated($request);

        $don->update($data);

        return redirect()
            ->route('dons.show', $don)
            ->with('status', 'Don mis à jour.');
    }

    public function destroy(Don $don): RedirectResponse
    {
        $don->delete();

        return redirect()
            ->route('dons.index')
            ->with('status', 'Don supprimé.');
    }

    public function updateStatus(Request $request, Don $don): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:'.implode(',', Don::STATUTS)],
        ]);

        $don->update($data + [
            'date_don' => $data['statut'] === 'remis' ? now()->toDateString() : $don->date_don,
        ]);

        return back()->with('status', "Statut du don #{$don->id} mis à jour.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'vetement_titre' => ['required', 'string', 'max:255'],
            'donateur_id' => ['required', 'exists:users,id'],
            'association_id' => ['required', 'exists:associations,id'],
            'date_don' => ['nullable', 'date'],
            'mode' => ['required', 'in:'.implode(',', Don::MODES)],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}

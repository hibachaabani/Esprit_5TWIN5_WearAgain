<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvisRequest;
use App\Http\Requests\UpdateAvisRequest;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\User;
use Illuminate\Http\Request;

class AvisController extends Controller
{
    /**
     * Back office : statistiques + liste paginée des avis.
     */
    public function index(Request $request)
    {
        $stats = [
            'total' => Avis::count(),
            'moyenne' => (float) Avis::query()->avg('note'),
            'cinq' => Avis::where('note', 5)->count(),
            'faibles' => Avis::where('note', '<', 3)->count(),
        ];

        $query = Avis::query()->with('user');

        if (class_exists(Atelier::class)) {
            $query->with('atelier');
        }

        if ($request->filled('note')) {
            $query->where('note', $request->integer('note'));
        }

        $avis = $query->latest()->paginate(10)->withQueryString();

        return view('admin.avis.index', compact('avis', 'stats'));
    }

    public function show(Avis $avi)
    {
        $avi->load('user');

        if (class_exists(Atelier::class)) {
            $avi->load('atelier');
        }

        return view('admin.avis.show', compact('avi'));
    }

    public function create()
    {
        return view('admin.avis.create', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreAvisRequest $request)
    {
        $data = $request->validated();
        $data['date'] = $data['date'] ?? now();

        Avis::create($data);

        return redirect()
            ->route('admin.avis.index')
            ->with('status', 'Avis créé avec succès.');
    }

    public function edit(Avis $avi)
    {
        return view('admin.avis.edit', [
            'avi' => $avi,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAvisRequest $request, Avis $avi)
    {
        $avi->update($request->validated());

        return redirect()
            ->route('admin.avis.index')
            ->with('status', 'Avis mis à jour avec succès.');
    }

    public function destroy(Avis $avi)
    {
        $avi->delete();

        return redirect()
            ->route('admin.avis.index')
            ->with('status', 'Avis supprimé avec succès.');
    }
}
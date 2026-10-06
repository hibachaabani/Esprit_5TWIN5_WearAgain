<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvisRequest;
use App\Http\Requests\UpdateAvisRequest;
use App\Models\Avis;
use App\Models\Reparation;
use App\Models\User;

class AvisController extends Controller
{
    /**
     * Back office : liste des avis.
     */
    public function index()
    {
        $avis = Avis::query()
            ->with(['reparation', 'user'])
            ->latest()
            ->paginate(10);

        return view('admin.avis.index', compact('avis'));
    }

    public function create()
    {
        return view('admin.avis.create', [
            'reparations' => Reparation::orderBy('titre')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreAvisRequest $request)
    {
        Avis::create($request->validated());

        return redirect()
            ->route('admin.avis.index')
            ->with('status', 'Avis créé avec succès.');
    }

    public function edit(Avis $avi)
    {
        return view('admin.avis.edit', [
            'avi' => $avi,
            'reparations' => Reparation::orderBy('titre')->get(),
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

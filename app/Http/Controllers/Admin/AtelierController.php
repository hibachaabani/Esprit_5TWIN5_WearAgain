<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAtelierRequest;
use App\Http\Requests\UpdateAtelierRequest;
use App\Models\Atelier;
use App\Models\User;
use Illuminate\Http\Request;

class AtelierController extends Controller
{
    /**
     * Back office : liste des ateliers.
     */
    public function index(Request $request)
    {
        $query = Atelier::query()->with(['user'])->withCount('transformations')->latest();

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('ville', 'like', "%{$search}%");
            });
        }

        $ateliers = $query->paginate(10)->withQueryString();

        return view('admin.ateliers.index', compact('ateliers'));
    }

    public function create()
    {
        return view('admin.ateliers.create', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreAtelierRequest $request)
    {
        Atelier::create($request->validated());

        return redirect()
            ->route('admin.ateliers.index')
            ->with('status', 'Atelier créé avec succès.');
    }

    public function edit(Atelier $atelier)
    {
        return view('admin.ateliers.edit', [
            'atelier' => $atelier,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAtelierRequest $request, Atelier $atelier)
    {
        $atelier->update($request->validated());

        return redirect()
            ->route('admin.ateliers.index')
            ->with('status', 'Atelier mis à jour avec succès.');
    }

    public function destroy(Atelier $atelier)
    {
        $atelier->delete();

        return redirect()
            ->route('admin.ateliers.index')
            ->with('status', 'Atelier supprimé avec succès.');
    }
}

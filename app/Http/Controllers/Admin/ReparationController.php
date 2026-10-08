<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReparationRequest;
use App\Http\Requests\UpdateReparationRequest;
use App\Models\Reparation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ReparationController extends Controller
{
    private const IMAGE_DIR = 'uploads/reparations';

    /**
     * Back office : tableau de bord + liste des réparations.
     */
    public function index(Request $request)
    {
        $compteurs = Reparation::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $query = Reparation::query()->with(['user', 'avis'])->withCount('avis')->latest();

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        $reparations = $query->paginate(10)->withQueryString();

        return view('admin.reparations.index', compact('reparations', 'compteurs'));
    }

    public function create()
    {
        return view('admin.reparations.create', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreReparationRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        }

        Reparation::create($data);

        return redirect()
            ->route('admin.reparations.index')
            ->with('status', 'Réparation créée avec succès.');
    }

    public function edit(Reparation $reparation)
    {
        return view('admin.reparations.edit', [
            'reparation' => $reparation,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateReparationRequest $request, Reparation $reparation)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($reparation);
            $data['image'] = $this->storeImage($request->file('image'));
        } else {
            $data['image'] = $reparation->image;
        }

        $reparation->update($data);

        return redirect()
            ->route('admin.reparations.index')
            ->with('status', 'Réparation mise à jour avec succès.');
    }

    public function destroy(Reparation $reparation)
    {
        $this->deleteImage($reparation);
        $reparation->delete();

        return redirect()
            ->route('admin.reparations.index')
            ->with('status', 'Réparation supprimée avec succès.');
    }

    private function storeImage(UploadedFile $file): string
    {
        $name = uniqid('reparation_', true).'.'.$file->getClientOriginalExtension();
        $file->move(public_path(self::IMAGE_DIR), $name);

        return $name;
    }

    private function deleteImage(Reparation $reparation): void
    {
        if (! $reparation->hasImage()) {
            return;
        }

        @unlink(public_path(self::IMAGE_DIR.'/'.$reparation->image));
    }
}

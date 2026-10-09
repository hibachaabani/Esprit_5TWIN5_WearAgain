<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransformationRequest;
use App\Http\Requests\UpdateTransformationRequest;
use App\Models\Atelier;
use App\Models\Transformation;
use App\Models\Vetement;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class TransformationController extends Controller
{
    private const IMAGE_DIR = 'uploads/transformations';

    /**
     * Back office : tableau de bord + liste des transformations.
     */
    public function index(Request $request)
    {
        $compteurs = Transformation::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $query = Transformation::query()->with(['atelier', 'vetement'])->latest();

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        $transformations = $query->paginate(10)->withQueryString();

        return view('admin.transformations.index', compact('transformations', 'compteurs'));
    }

    public function create()
    {
        return view('admin.transformations.create', [
            'ateliers' => Atelier::orderBy('nom')->get(),
            'vetements' => Vetement::orderBy('titre')->get(),
        ]);
    }

    public function store(StoreTransformationRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image_resultat')) {
            $data['image_resultat'] = $this->storeImage($request->file('image_resultat'));
        }

        Transformation::create($data);

        return redirect()
            ->route('admin.transformations.index')
            ->with('status', 'Transformation créée avec succès.');
    }

    public function edit(Transformation $transformation)
    {
        return view('admin.transformations.edit', [
            'transformation' => $transformation,
            'ateliers' => Atelier::orderBy('nom')->get(),
            'vetements' => Vetement::orderBy('titre')->get(),
        ]);
    }

    public function update(UpdateTransformationRequest $request, Transformation $transformation)
    {
        $data = $request->validated();

        if ($request->hasFile('image_resultat')) {
            $this->deleteImage($transformation);
            $data['image_resultat'] = $this->storeImage($request->file('image_resultat'));
        } else {
            $data['image_resultat'] = $transformation->image_resultat;
        }

        $transformation->update($data);

        return redirect()
            ->route('admin.transformations.index')
            ->with('status', 'Transformation mise à jour avec succès.');
    }

    public function destroy(Transformation $transformation)
    {
        $this->deleteImage($transformation);
        $transformation->delete();

        return redirect()
            ->route('admin.transformations.index')
            ->with('status', 'Transformation supprimée avec succès.');
    }

    private function storeImage(UploadedFile $file): string
    {
        $name = uniqid('transformation_', true).'.'.$file->getClientOriginalExtension();
        $file->move(public_path(self::IMAGE_DIR), $name);

        return $name;
    }

    private function deleteImage(Transformation $transformation): void
    {
        if (! $transformation->hasImage()) {
            return;
        }

        @unlink(public_path(self::IMAGE_DIR.'/'.$transformation->image_resultat));
    }
}

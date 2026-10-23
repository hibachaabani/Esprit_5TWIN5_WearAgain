<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Models\Vetement;
use Illuminate\View\View;

/**
 * Page d'accueil : derniers vêtements, besoins urgents des associations,
 * chiffres de la plateforme et point d'entrée de l'assistant de don IA.
 */
class AccueilController extends Controller
{
    public function __invoke(): View
    {
        $items = Vetement::with(['categorie', 'photos'])
            ->where('statut', 'disponible')
            ->latest()
            ->take(4)
            ->get();

        // Les plus urgents d'abord ; un besoin déjà couvert par les dons en route n'est pas mis en avant.
        $besoinsUrgents = Besoin::ouverts()
            ->avecProgression()
            ->with('association')
            ->whereHas('association', fn ($q) => $q->recoitDesDons())
            ->parPriorite()
            ->take(12)
            ->get()
            ->reject(fn (Besoin $besoin) => $besoin->estComplet())
            ->take(3);

        $chiffres = [
            'vetements' => Vetement::where('statut', 'disponible')->count(),
            'dons' => Don::where('statut', 'remis')->count(),
            'associations' => Association::recoitDesDons()->count(),
            'besoins' => Besoin::ouverts()->whereHas('association', fn ($q) => $q->recoitDesDons())->count(),
        ];

        return view('home', compact('items', 'besoinsUrgents', 'chiffres'));
    }
}

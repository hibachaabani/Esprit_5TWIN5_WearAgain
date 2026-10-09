<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Back-office : comptes de la plateforme, filtrables par rôle.
 */
class UtilisateurController extends Controller
{
    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_keys(User::ROLES))],
        ]);

        $utilisateurs = User::with(['association', 'atelier'])
            ->withCount(['vetements', 'dons', 'depots'])
            ->when($filtres['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($filtres['q'] ?? null, fn ($q, $terme) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$terme}%")
                ->orWhere('email', 'like', "%{$terme}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.utilisateurs.index', [
            'utilisateurs' => $utilisateurs,
            'filtres' => $filtres,
            'compteurs' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
        ]);
    }
}

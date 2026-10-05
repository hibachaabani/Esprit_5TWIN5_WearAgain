<?php

namespace App\Http\Requests;

use App\Models\PointCollecte;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $depot = $this->route('depot');

        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'point_collecte_id' => ['bail', 'required', 'integer', 'exists:points_collecte,id', $this->pointAccepteLeDepot()],
            'vetement_titre' => ['required', 'string', 'min:3', 'max:255'],
            // Un nouveau dépôt se planifie entre aujourd'hui et dans 30 jours.
            'date_depot' => [
                'required', 'date',
                Rule::when(! $depot, 'after_or_equal:today'),
                'before_or_equal:'.now()->addDays(30)->toDateString(),
            ],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Le point choisi doit être ouvert et avoir encore de la place,
     * sauf s'il s'agit du point actuel d'un dépôt qu'on modifie.
     */
    private function pointAccepteLeDepot(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $depot = $this->route('depot');
            if ($depot && (int) $value === (int) $depot->point_collecte_id) {
                return;
            }

            $point = PointCollecte::withCount('depotsActifs')->find($value);

            if (! $point->actif) {
                $fail('Ce point de collecte est fermé pour le moment.');
            } elseif ($point->estComplet()) {
                $fail('Ce point de collecte est complet, choisissez-en un autre.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être un texte.',
            'integer' => 'Le champ :attribute est invalide.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'user_id.exists' => 'Ce particulier n\'existe pas.',
            'point_collecte_id.exists' => 'Ce point de collecte n\'existe pas.',
            'vetement_titre.min' => 'Décrivez le vêtement en au moins :min caractères.',
            'date_depot.date' => 'La date de dépôt n\'est pas valide.',
            'date_depot.after_or_equal' => 'La date de dépôt ne peut pas être dans le passé.',
            'date_depot.before_or_equal' => 'Un dépôt se planifie au plus 30 jours à l\'avance.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'particulier',
            'point_collecte_id' => 'point de collecte',
            'vetement_titre' => 'vêtement',
            'date_depot' => 'date de dépôt',
        ];
    }
}

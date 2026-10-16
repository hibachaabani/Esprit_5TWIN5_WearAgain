<?php

namespace App\Http\Requests;

use App\Models\Avis;
use App\Models\Reparation;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Back-office : l'admin saisit un avis à partir d'une réparation terminée.
 * L'auteur et l'atelier sont déduits de la réparation (jamais choisis à la main),
 * ce qui garantit la règle métier : un avis par membre et par atelier.
 */
class StoreAvisAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reparation_id' => [
                'required',
                'integer',
                Rule::exists('reparations', 'id'),
                function (string $attribute, $value, Closure $fail) {
                    $reparation = Reparation::with('vetement')->find($value);

                    if (! $reparation) {
                        return;
                    }

                    if ($reparation->statut !== 'terminee') {
                        $fail('La réparation choisie doit être terminée.');

                        return;
                    }

                    if (! $reparation->atelier_id) {
                        $fail('La réparation choisie n\'est rattachée à aucun atelier.');

                        return;
                    }

                    $auteur = $reparation->vetement?->user_id;

                    if (! $auteur) {
                        $fail('La réparation choisie n\'a pas d\'auteur identifiable.');

                        return;
                    }

                    if (Avis::where('user_id', $auteur)->where('atelier_id', $reparation->atelier_id)->exists()) {
                        $fail('Un avis existe déjà pour ce membre et cet atelier.');
                    }
                },
            ],
            'note' => ['required', 'integer', 'between:1,5'],
            'commentaire' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reparation_id.required' => 'Veuillez choisir la réparation à l\'origine de l\'avis.',
            'reparation_id.exists' => 'Cette réparation n\'existe pas.',
            'note.required' => 'La note est obligatoire.',
            'note.between' => 'La note doit être comprise entre 1 et 5 étoiles.',
            'commentaire.required' => 'Le commentaire est obligatoire.',
            'commentaire.min' => 'Le commentaire doit contenir au moins 5 caractères.',
            'commentaire.max' => 'Le commentaire ne doit pas dépasser 1000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reparation_id' => 'réparation',
            'note' => 'note',
            'commentaire' => 'commentaire',
        ];
    }
}

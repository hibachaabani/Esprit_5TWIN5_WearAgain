<?php

namespace App\Http\Requests;

use App\Models\Association;
use App\Models\Besoin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BesoinRequest extends FormRequest
{
    /**
     * Un compte association publie ses besoins ; seule l'association du besoin le modifie.
     */
    public function authorize(): bool
    {
        $besoin = $this->route('besoin');

        return $besoin ? $besoin->estGerePar($this->user()) : $this->user()?->association !== null;
    }

    public function rules(): array
    {
        $besoin = $this->route('besoin');

        return [
            'titre' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'categorie' => ['required', Rule::in(array_keys(Association::CATEGORIES))],
            // On ne peut pas viser moins que ce qui a déjà été remis.
            'quantite' => ['required', 'integer', 'min:'.max(1, $besoin?->quantiteRecue() ?? 0), 'max:10000'],
            'urgence' => ['required', Rule::in(array_keys(Besoin::URGENCES))],
            // À la création, la date limite ne peut pas être déjà passée.
            'date_limite' => array_filter(['nullable', 'date', $besoin ? null : 'after_or_equal:today']),
            'statut' => [$besoin ? 'required' : 'prohibited', Rule::in(array_keys(Besoin::STATUTS))],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être un texte.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'in' => 'La valeur choisie pour :attribute est invalide.',
            'titre.min' => 'Le titre doit contenir au moins :min caractères.',
            'quantite.integer' => 'La quantité doit être un nombre entier.',
            'quantite.min' => 'La quantité doit être d\'au moins :min (vêtements déjà remis compris).',
            'quantite.max' => 'La quantité ne peut pas dépasser :max vêtements.',
            'date_limite.date' => 'La date limite est invalide.',
            'date_limite.after_or_equal' => 'La date limite ne peut pas être dans le passé.',
            'statut.prohibited' => 'Un nouveau besoin est toujours ouvert.',
        ];
    }

    public function attributes(): array
    {
        return [
            'categorie' => 'catégorie',
            'quantite' => 'quantité',
            'date_limite' => 'date limite',
        ];
    }
}

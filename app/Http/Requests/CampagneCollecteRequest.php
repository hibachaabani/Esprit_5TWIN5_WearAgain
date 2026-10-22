<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Campagne de collecte d'une association. Le point choisi doit appartenir à l'association
 * connectée ; sans point, la campagne couvre tous ses points.
 */
class CampagneCollecteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creation = $this->isMethod('post');

        return [
            'titre' => ['required', 'string', 'min:5', 'max:120'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'objectif' => ['required', 'integer', 'min:1', 'max:10000'],
            'point_collecte_id' => [
                'nullable',
                'integer',
                Rule::exists('points_collecte', 'id')->where('association_id', $this->user()?->association?->id),
            ],
            'date_debut' => array_filter(['required', 'date', $creation ? 'after_or_equal:today' : null]),
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut', 'before_or_equal:'.today()->addYear()->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'min' => 'Le champ :attribute doit contenir au moins :min caractères.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'objectif.integer' => 'L\'objectif doit être un nombre entier de vêtements.',
            'objectif.min' => 'L\'objectif doit être d\'au moins :min vêtement.',
            'objectif.max' => 'L\'objectif ne peut pas dépasser :max vêtements.',
            'point_collecte_id.exists' => 'Choisissez un de vos points de collecte.',
            'date_debut.after_or_equal' => 'La campagne ne peut pas commencer dans le passé.',
            'date_fin.after_or_equal' => 'La date de fin doit être après la date de début.',
            'date_fin.before_or_equal' => 'Une campagne dure au plus un an.',
        ];
    }

    public function attributes(): array
    {
        return [
            'titre' => 'titre',
            'objectif' => 'objectif',
            'point_collecte_id' => 'point de collecte',
            'date_debut' => 'date de début',
            'date_fin' => 'date de fin',
        ];
    }
}

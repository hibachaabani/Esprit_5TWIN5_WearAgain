<?php

namespace App\Http\Requests;

use App\Models\Signalement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Signalement d'un problème sur un point de collecte (création et modification par son auteur).
 * Le point ne se choisit qu'à la création : un signalement reste attaché à son point.
 */
class SignalementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regles = [
            'type' => ['required', Rule::in(array_keys(Signalement::TYPES))],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
        ];

        if ($this->isMethod('post')) {
            $regles['point_collecte_id'] = ['required', 'integer', 'exists:points_collecte,id'];
        }

        return $regles;
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'type.in' => 'Choisissez un type de problème dans la liste.',
            'message.min' => 'Décrivez le problème en au moins :min caractères.',
            'message.max' => 'Le message ne doit pas dépasser :max caractères.',
            'point_collecte_id.exists' => 'Ce point de collecte n\'existe pas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'type de problème',
            'point_collecte_id' => 'point de collecte',
        ];
    }
}

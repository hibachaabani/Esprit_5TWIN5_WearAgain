<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PointCollecteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['actif' => $this->boolean('actif')]);
    }

    public function rules(): array
    {
        $point = $this->route('pointCollecte');

        return [
            'nom' => [
                'required', 'string', 'min:3', 'max:255',
                Rule::unique('points_collecte')->where('ville', $this->input('ville'))->ignore($point),
            ],
            'adresse' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'horaires' => ['nullable', 'string', 'max:255'],
            // On ne peut pas réduire la capacité sous le nombre de vêtements déjà présents.
            'capacite' => ['required', 'integer', 'min:'.max(1, $point?->placesOccupees() ?? 0), 'max:10000'],
            'actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être un texte.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'numeric' => 'Le champ :attribute doit être un nombre.',
            'boolean' => 'Le champ :attribute est invalide.',
            'nom.min' => 'Le nom doit contenir au moins :min caractères.',
            'nom.unique' => 'Un point de collecte porte déjà ce nom dans cette ville.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
            'capacite.integer' => 'La capacité doit être un nombre entier.',
            'capacite.min' => 'La capacité doit être d\'au moins :min vêtement(s) (vêtements déjà présents compris).',
            'capacite.max' => 'La capacité ne peut pas dépasser :max vêtements.',
        ];
    }

    public function attributes(): array
    {
        return [
            'capacite' => 'capacité',
        ];
    }
}

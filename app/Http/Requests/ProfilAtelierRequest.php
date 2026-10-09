<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Un compte atelier complète ou modifie la fiche de son atelier.
 */
class ProfilAtelierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estAtelier() ?? false;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'min:10', 'max:2000'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'specialites' => ['nullable', 'string', 'max:255'],
            'horaires' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => "Le nom de l'atelier est obligatoire.",
            'nom.min' => 'Le nom doit contenir au moins 3 caractères.',
            'nom.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'max' => 'Le champ :attribute est trop long.',
        ];
    }
}

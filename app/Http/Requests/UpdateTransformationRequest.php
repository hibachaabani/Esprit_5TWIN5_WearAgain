<?php

namespace App\Http\Requests;

use App\Models\Transformation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vetement_id' => ['required', 'exists:vetements,id'],
            'atelier_id' => ['required', 'exists:ateliers,id'],
            'type' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'min:10'],
            'prix' => ['required', 'numeric', 'min:0'],
            'image_resultat' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'statut' => ['required', Rule::in(array_keys(Transformation::STATUTS))],
        ];
    }

    public function messages(): array
    {
        return [
            'vetement_id.required' => 'Veuillez choisir un vêtement.',
            'vetement_id.exists' => "Ce vêtement n'existe pas.",
            'atelier_id.required' => 'Veuillez choisir un atelier.',
            'atelier_id.exists' => "Cet atelier n'existe pas.",
            'type.required' => 'Le type de transformation est obligatoire.',
            'type.min' => 'Le type doit contenir au moins 3 caractères.',
            'type.max' => 'Le type ne peut pas dépasser 100 caractères.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'image_resultat.image' => 'Le fichier doit être une image.',
            'image_resultat.mimes' => "L'image doit être au format jpeg, png ou webp.",
            'image_resultat.max' => "L'image ne doit pas dépasser 2 Mo.",
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être : en attente, en cours ou terminée.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Transformation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * L'atelier fixe le prix, fait avancer la transformation et ajoute la photo du résultat.
 */
class TraiterTransformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('transformation')?->atelier?->estGerePar($this->user()) ?? false;
    }

    public function rules(): array
    {
        return [
            'prix' => ['required', 'numeric', 'min:0', 'max:100000'],
            'statut' => ['required', Rule::in(array_keys(Transformation::STATUTS))],
            'image_resultat' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'prix.max' => 'Le prix semble trop élevé.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être : en attente, en cours ou terminée.',
            'image_resultat.image' => 'Le fichier doit être une image.',
            'image_resultat.mimes' => "L'image doit être au format jpeg, png ou webp.",
            'image_resultat.max' => "L'image ne doit pas dépasser 2 Mo.",
        ];
    }
}

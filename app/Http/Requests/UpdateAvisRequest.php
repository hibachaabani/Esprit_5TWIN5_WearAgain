<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Back-office : l'admin modère un avis existant.
 * Seuls la note et le commentaire sont modifiables ; l'auteur et l'atelier
 * restent ceux de la réparation d'origine.
 */
class UpdateAvisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'integer', 'between:1,5'],
            'commentaire' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'La note est obligatoire.',
            'note.integer' => 'La note doit être un nombre entier.',
            'note.between' => 'La note doit être comprise entre 1 et 5 étoiles.',
            'commentaire.required' => 'Le commentaire est obligatoire.',
            'commentaire.min' => 'Le commentaire doit contenir au moins 5 caractères.',
            'commentaire.max' => 'Le commentaire ne doit pas dépasser 1000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'note' => 'note',
            'commentaire' => 'commentaire',
        ];
    }
}

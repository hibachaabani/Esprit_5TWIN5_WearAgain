<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reparation_id' => ['required', 'exists:reparations,id'],
            'user_id' => ['required', 'exists:users,id'],
            'note' => ['required', 'integer', 'min:1', 'max:5'],
            'commentaire' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reparation_id.required' => 'Veuillez choisir une réparation.',
            'reparation_id.exists' => 'Cette réparation n\'existe pas.',
            'user_id.required' => 'Veuillez choisir un membre.',
            'user_id.exists' => 'Ce membre n\'existe pas.',
            'note.required' => 'La note est obligatoire.',
            'note.integer' => 'La note doit être un nombre entier.',
            'note.min' => 'La note minimale est de 1 étoile.',
            'note.max' => 'La note maximale est de 5 étoiles.',
            'commentaire.required' => 'Le commentaire est obligatoire.',
            'commentaire.min' => 'Le commentaire doit contenir au moins 5 caractères.',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 1000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'reparation_id' => 'réparation',
            'user_id' => 'membre',
            'note' => 'note',
            'commentaire' => 'commentaire',
        ];
    }
}

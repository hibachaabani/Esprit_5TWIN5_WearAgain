<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAtelierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'nom' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'min:10'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'specialites' => ['nullable', 'string', 'max:255'],
            'horaires' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Veuillez choisir un membre.',
            'user_id.exists' => "Ce membre n'existe pas.",
            'nom.required' => "Le nom de l'atelier est obligatoire.",
            'nom.min' => 'Le nom doit contenir au moins 3 caractères.',
            'nom.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
        ];
    }
}

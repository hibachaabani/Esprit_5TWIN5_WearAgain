<?php

namespace App\Http\Requests;

use App\Models\Reparation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReparationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'titre' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['required', 'string', 'min:10'],
            'statut' => ['required', Rule::in(array_keys(Reparation::STATUTS))],
            'cout_estime' => ['required', 'numeric', 'min:0'],
            'date_reparation' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Veuillez choisir un membre.',
            'user_id.exists' => 'Ce membre n\'existe pas.',
            'titre.required' => 'Le titre de la réparation est obligatoire.',
            'titre.min' => 'Le titre doit contenir au moins 3 caractères.',
            'titre.max' => 'Le titre ne peut pas dépasser 100 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être : en attente, en cours ou terminée.',
            'cout_estime.required' => 'Le coût estimé est obligatoire.',
            'cout_estime.numeric' => 'Le coût estimé doit être un nombre.',
            'cout_estime.min' => 'Le coût estimé ne peut pas être négatif.',
            'date_reparation.required' => 'La date de réparation est obligatoire.',
            'date_reparation.date' => 'La date de réparation n\'est pas valide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'membre',
            'titre' => 'titre',
            'description' => 'description',
            'statut' => 'statut',
            'cout_estime' => 'coût estimé',
            'date_reparation' => 'date de réparation',
        ];
    }
}

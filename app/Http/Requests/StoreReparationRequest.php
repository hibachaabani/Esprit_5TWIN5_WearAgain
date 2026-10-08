<?php

namespace App\Http\Requests;

use App\Models\Reparation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreReparationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'vetement_id' => ['required', 'exists:vetements,id'],
            'atelier_id' => ['nullable'],
            'description_probleme' => ['required', 'string', 'min:10'],
            'devis' => ['nullable', 'numeric', 'min:0'],
            'date_rdv' => ['required', 'date'],
            'statut' => ['required', Rule::in(array_keys(Reparation::STATUTS))],
        ];

        if (Schema::hasTable('ateliers')) {
            $rules['atelier_id'][] = Rule::exists('ateliers', 'id');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'vetement_id.required' => 'Veuillez choisir un vêtement.',
            'vetement_id.exists' => 'Ce vêtement n\'existe pas.',
            'atelier_id.exists' => 'Cet atelier n\'existe pas.',
            'description_probleme.required' => 'La description du problème est obligatoire.',
            'description_probleme.min' => 'La description doit contenir au moins 10 caractères.',
            'devis.numeric' => 'Le devis doit être un nombre.',
            'devis.min' => 'Le devis ne peut pas être négatif.',
            'date_rdv.required' => 'La date de rendez-vous est obligatoire.',
            'date_rdv.date' => 'La date de rendez-vous n\'est pas valide.',
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être : en attente, en cours ou terminée.',
        ];
    }

    public function attributes(): array
    {
        return [
            'vetement_id' => 'vêtement',
            'atelier_id' => 'atelier',
            'description_probleme' => 'description du problème',
            'devis' => 'devis',
            'date_rdv' => 'date de rendez-vous',
            'statut' => 'statut',
        ];
    }
}
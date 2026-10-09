<?php

namespace App\Http\Requests;

use App\Models\Reparation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * L'atelier propose son devis, fixe le rendez-vous et fait avancer la réparation.
 */
class TraiterReparationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('reparation')?->atelier?->estGerePar($this->user()) ?? false;
    }

    public function rules(): array
    {
        return [
            'devis' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'date_rdv' => ['nullable', 'date'],
            'statut' => ['required', Rule::in(array_keys(Reparation::STATUTS))],
        ];
    }

    public function messages(): array
    {
        return [
            'devis.numeric' => 'Le devis doit être un nombre.',
            'devis.min' => 'Le devis ne peut pas être négatif.',
            'devis.max' => 'Le devis semble trop élevé.',
            'date_rdv.date' => "La date de rendez-vous n'est pas valide.",
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être : en attente, en cours ou terminée.',
        ];
    }
}

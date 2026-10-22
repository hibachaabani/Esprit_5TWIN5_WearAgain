<?php

namespace App\Http\Requests;

use App\Models\Transformation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * L'atelier propose un devis (montant + délai en jours + message) pour une
 * transformation reçue, avant de commencer le travail.
 */
class ProposerDevisTransformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('transformation')?->atelier?->estGerePar($this->user()) ?? false;
    }

    public function rules(): array
    {
        return [
            'devis_prix' => ['required', 'numeric', 'min:0.5', 'max:100000'],
            'devis_delai_jours' => ['required', 'integer', 'min:1', 'max:365'],
            'devis_message' => ['nullable', 'string', 'max:1000'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'devis_prix.required' => 'Le montant du devis est obligatoire.',
            'devis_prix.numeric' => 'Le montant du devis doit être un nombre.',
            'devis_prix.min' => 'Le montant du devis doit être supérieur à 0.',
            'devis_prix.max' => 'Le montant du devis semble trop élevé.',
            'devis_delai_jours.required' => 'Le délai en jours est obligatoire.',
            'devis_delai_jours.integer' => 'Le délai doit être un nombre de jours.',
            'devis_delai_jours.min' => 'Le délai doit être d\'au moins 1 jour.',
            'devis_delai_jours.max' => 'Le délai ne peut pas dépasser 365 jours.',
            'devis_message.max' => 'Le message du devis ne doit pas dépasser 1000 caractères.',
            'commentaire.max' => 'Le commentaire ne doit pas dépasser 1000 caractères.',
        ];
    }
}
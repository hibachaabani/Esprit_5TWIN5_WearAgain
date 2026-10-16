<?php

namespace App\Http\Requests;

use App\Models\Reparation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * L'atelier propose (ou révise) son devis et fixe le rendez-vous.
 */
class ProposerDevisRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reparation = $this->route('reparation');

        return $reparation instanceof Reparation
            && $this->user() !== null
            && $this->user()->can('gererDevis', $reparation);
    }

    public function rules(): array
    {
        return [
            'devis' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'date_rdv' => ['required', 'date'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'devis.required' => 'Indiquez le montant du devis.',
            'devis.numeric' => 'Le devis doit être un nombre.',
            'devis.gt' => 'Le devis doit être supérieur à 0.',
            'devis.max' => 'Le devis semble trop élevé.',
            'date_rdv.required' => 'Choisissez une date de rendez-vous.',
            'date_rdv.date' => "La date de rendez-vous n'est pas valide.",
            'commentaire.max' => 'Le commentaire ne doit pas dépasser 1000 caractères.',
        ];
    }
}

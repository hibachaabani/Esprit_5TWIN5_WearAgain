<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Le particulier demande la réparation d'un de ses vêtements à un atelier.
 */
class DemandeReparationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->estParticulier() ?? false;
    }

    public function rules(): array
    {
        return [
            'vetement_id' => ['required', 'integer', Rule::exists('vetements', 'id')->where('user_id', $this->user()->id)],
            'atelier_id' => ['required', 'integer', 'exists:ateliers,id'],
            'description_probleme' => ['required', 'string', 'min:10', 'max:2000'],
            'date_rdv' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'vetement_id.required' => 'Choisissez le vêtement à réparer.',
            'vetement_id.exists' => 'Choisissez un de vos vêtements publiés.',
            'atelier_id.required' => 'Choisissez un atelier.',
            'atelier_id.exists' => "Cet atelier n'existe pas.",
            'description_probleme.required' => 'Décrivez le problème à réparer.',
            'description_probleme.min' => 'La description doit contenir au moins 10 caractères.',
            'description_probleme.max' => 'La description ne doit pas dépasser 2000 caractères.',
            'date_rdv.date' => "La date souhaitée n'est pas valide.",
            'date_rdv.after' => 'La date souhaitée doit être dans le futur.',
        ];
    }
}

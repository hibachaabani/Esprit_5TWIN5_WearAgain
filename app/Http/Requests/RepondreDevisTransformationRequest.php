<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le particulier propriétaire du vêtement répond au devis reçu :
 * il l'accepte (la transformation démarre) ou le refuse (retour en attente).
 */
class RepondreDevisTransformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->route('transformation')?->vetement?->user_id ?? null) === $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'commentaire.max' => 'Le commentaire ne doit pas dépasser 1000 caractères.',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le client accepte ou refuse le devis, avec un commentaire facultatif.
 */
class RepondreDevisRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le rôle est vérifié par la policy dans le contrôleur.
        return $this->user() !== null;
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

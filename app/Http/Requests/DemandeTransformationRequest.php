<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Le particulier demande à un atelier de transformer un de ses vêtements (upcycling).
 */
class DemandeTransformationRequest extends FormRequest
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
            'type' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'vetement_id.required' => 'Choisissez le vêtement à transformer.',
            'vetement_id.exists' => 'Choisissez un de vos vêtements publiés.',
            'atelier_id.required' => 'Choisissez un atelier.',
            'atelier_id.exists' => "Cet atelier n'existe pas.",
            'type.required' => 'Indiquez ce que vous souhaitez obtenir (sac, coussin…).',
            'type.min' => 'Le type doit contenir au moins 3 caractères.',
            'type.max' => 'Le type ne peut pas dépasser 100 caractères.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'description.max' => 'La description ne doit pas dépasser 2000 caractères.',
        ];
    }
}

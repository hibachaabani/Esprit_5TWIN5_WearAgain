<?php

namespace App\Http\Requests;

use App\Models\Reparation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoreAvisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'user_id' => ['required', 'exists:users,id'],
            'atelier_id' => ['nullable'],
            'note' => ['required', 'integer', 'between:1,5'],
            'commentaire' => ['required', 'string', 'min:5'],
            'date' => ['nullable', 'date'],
        ];

        if (Schema::hasTable('ateliers')) {
            // Le diagramme impose un atelier par avis (Atelier "1" -- "0..*" Avis).
            $rules['atelier_id'] = ['required', Rule::exists('ateliers', 'id')];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        // Front office : l'atelier est repris de la réparation liée (route /reparations/{reparation}/avis).
        // L'auteur est toujours le membre connecté : il n'est jamais choisi dans le formulaire.
        if (($reparation = $this->route('reparation')) instanceof Reparation) {
            $this->merge(['atelier_id' => $reparation->atelier_id, 'user_id' => $this->user()?->id]);
        }
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Veuillez choisir un membre.',
            'user_id.exists' => 'Ce membre n\'existe pas.',
            'atelier_id.required' => 'L\'atelier est obligatoire.',
            'atelier_id.exists' => 'Cet atelier n\'existe pas.',
            'note.required' => 'La note est obligatoire.',
            'note.integer' => 'La note doit être un nombre entier.',
            'note.between' => 'La note doit être comprise entre 1 et 5 étoiles.',
            'commentaire.required' => 'Le commentaire est obligatoire.',
            'commentaire.min' => 'Le commentaire doit contenir au moins 5 caractères.',
            'date.date' => 'La date n\'est pas valide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'membre',
            'atelier_id' => 'atelier',
            'note' => 'note',
            'commentaire' => 'commentaire',
            'date' => 'date',
        ];
    }
}

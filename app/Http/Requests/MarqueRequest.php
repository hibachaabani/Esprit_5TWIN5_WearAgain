<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    public function rules(): array
    {
        $marque = $this->route('marque');

        return [
            'nom'          => [
                'required',
                'string',
                'max:100',
                Rule::unique('marques', 'nom')->ignore($marque?->id),
            ],
            'pays_origine' => 'nullable|string|max:100',
            'logo'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'site_web'     => 'nullable|url|max:255',
            'est_ethique'  => 'nullable|boolean',
            'description'  => 'nullable|string|max:1000',
            'categories'   => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'    => 'Le nom de la marque est obligatoire.',
            'nom.max'         => 'Le nom ne doit pas dépasser 100 caractères.',
            'nom.unique'      => 'Une marque avec ce nom existe déjà.',
            'logo.image'      => 'Le logo doit être une image.',
            'logo.mimes'      => 'Formats acceptés : JPEG, PNG, WebP.',
            'logo.max'        => 'Le logo ne doit pas dépasser 2 Mo.',
            'site_web.url'    => 'L\'adresse du site web n\'est pas valide.',
            'site_web.max'    => 'L\'adresse du site web ne doit pas dépasser 255 caractères.',
            'description.max' => 'La description ne doit pas dépasser 1000 caractères.',
        ];
    }
}

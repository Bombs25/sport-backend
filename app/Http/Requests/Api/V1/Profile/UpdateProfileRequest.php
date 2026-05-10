<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Rules\RasterImageFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ce qu'il fait : valide la mise à jour partielle du profil utilisateur (nom civil, pseudo, bio, confidentialité, date de naissance).
 *
 * Pourquoi : permettre l'édition du profil hors wizard d'inscription avec les mêmes contraintes métier qu'au register.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'given_name' => ['sometimes', 'required_with:family_name', 'string', 'max:120'],
            'family_name' => ['sometimes', 'required_with:given_name', 'string', 'max:120'],
            'handle' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:32',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('user_profiles', 'handle')->ignore($this->user()->id, 'user_id'),
            ],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_private' => ['sometimes', 'boolean'],
            'avatar_url' => ['sometimes', 'nullable', 'file', 'image', new RasterImageFile],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'address_line' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

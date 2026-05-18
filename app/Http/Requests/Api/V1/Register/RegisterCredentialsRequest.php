<?php

namespace App\Http\Requests\Api\V1\Register;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Ce qu’il fait : valide les entrées de `POST /api/v1/auth/register/credentials` (email unique + message explicite
 * si déjà pris, mot de passe fort, acceptation CGU, prénom + nom d’état civil obligatoires, ville + coordonnées GPS obligatoires).
 *
 * Pourquoi : centraliser les règles de validation ; `users.name` ne peut pas être null en base, donc le client
 * doit fournir le nom civil dès cette étape (aligné produit).
 */
class RegisterCredentialsRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'accept_terms' => ['required', 'accepted'],
            'given_name' => ['required', 'string', 'max:120'],
            'family_name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'fcm_token' => ['sometimes', 'nullable', 'string', 'max:512'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('Un compte existe déjà avec cette adresse e-mail.'),
        ];
    }
}

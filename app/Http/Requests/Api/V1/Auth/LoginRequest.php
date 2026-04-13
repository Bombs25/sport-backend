<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Ce qu’il fait : valide e-mail + mot de passe pour `POST /api/v1/auth/login` ; e-mail normalisé ; `accept_terms`
 * optionnel (écran « en vous connectant vous acceptez les CGU » côté mobile).
 *
 * Pourquoi : alignement casse e-mail avec inscription / mot de passe oublié ; pas de règles de complexité ici.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower((string) $this->input('email')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'accept_terms' => ['sometimes', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('L’adresse e-mail est obligatoire.'),
            'email.email' => __('L’adresse e-mail n’est pas valide.'),
            'password.required' => __('Le mot de passe est obligatoire.'),
            'accept_terms.accepted' => __('Vous devez accepter les conditions d’utilisation pour vous connecter.'),
        ];
    }
}

<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Ce qu'il fait : valide le changement direct de mot de passe pour l'utilisateur connecté.
 *
 * Pourquoi : flow simple côté paramètres compte sans OTP, protégé par le mot de passe actuel.
 */
class PasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('password_confirmation') && $this->has('passwordconfirmation')) {
            $this->merge([
                'password_confirmation' => $this->input('passwordconfirmation'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => __('La confirmation du mot de passe ne correspond pas.'),
            'password.different' => __('Le nouveau mot de passe doit être différent de l’ancien.'),
        ];
    }
}

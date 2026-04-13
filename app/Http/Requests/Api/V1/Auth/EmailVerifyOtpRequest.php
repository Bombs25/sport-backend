<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ce qu’il fait : valide le **code à 6 chiffres** pour `POST /api/v1/auth/email/verify`.
 *
 * Pourquoi : même contrat que l’écran mobile « Vérification » ; entrée strictement numérique sur 6 positions.
 */
class EmailVerifyOtpRequest extends FormRequest
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
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }
}

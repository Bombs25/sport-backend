<?php

namespace App\Http\Requests\Api\V1\Register;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ce qu’il fait : valide prénom, nom, pseudo (`handle`), date de naissance pour `PATCH …/register/profile`.
 *
 * Pourquoi : garde les contraintes métier (format pseudo, date passée) hors du service ; une seule source de vérité
 * pour les erreurs 422 renvoyées au client RN.
 */
class RegisterProfileRequest extends FormRequest
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
            'given_name' => ['required', 'string', 'max:120'],
            'family_name' => ['required', 'string', 'max:120'],
            'handle' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
            'birth_date' => ['required', 'date', 'before:today'],
        ];
    }
}

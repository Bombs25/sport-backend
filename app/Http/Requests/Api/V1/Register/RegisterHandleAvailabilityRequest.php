<?php

namespace App\Http\Requests\Api\V1\Register;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ce qu’il fait : valide le paramètre de requête `handle` pour `GET …/register/handle-availability`.
 *
 * Pourquoi : le pseudo vient en query string ; `prepareForValidation` le injecte comme champ validé pour réutiliser
 * les mêmes règles que le profil (longueur, caractères autorisés).
 */
class RegisterHandleAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'handle' => $this->query('handle'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'handle' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
        ];
    }
}

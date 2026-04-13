<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Ce qu'il fait : valide le nouvel e-mail pour demander un OTP de changement d'adresse.
 *
 * Pourquoi : empêcher collisions d'e-mails et normaliser l'adresse avant l'envoi du code.
 */
class EmailChangeRequest extends FormRequest
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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::notIn([Str::lower((string) $this->user()->email)]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.not_in' => __('La nouvelle adresse e-mail doit être différente de l’actuelle.'),
        ];
    }
}

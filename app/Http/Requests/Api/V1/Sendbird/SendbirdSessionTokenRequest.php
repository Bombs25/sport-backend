<?php

namespace App\Http\Requests\Api\V1\Sendbird;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Aucun paramètre : l'utilisateur authentifié obtient un token de session pour
 * son propre compte Sendbird.
 */
class SendbirdSessionTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}

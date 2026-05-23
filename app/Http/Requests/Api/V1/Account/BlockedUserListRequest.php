<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide `limit` (1-50) et `cursor` (string positive numérique) pour la liste
 * paginée des utilisateurs bloqués.
 */
class BlockedUserListRequest extends FormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'cursor' => ['sometimes', 'nullable', 'string', 'regex:/^\d+$/'],
        ];
    }
}

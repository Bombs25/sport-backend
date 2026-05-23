<?php

namespace App\Http\Requests\Api\V1\Messages;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide les params du picker « partager à » : `q` (recherche), `limit`, `page`.
 *
 * Pourquoi un endpoint distinct de `users/search` : on doit appliquer le
 * filtre `who_can_message_me` qui n'est pas dans Typesense (vit dans
 * `user_profiles`), donc on filtre côté app après le hit Typesense.
 */
class MessageableUsersSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ];
    }
}

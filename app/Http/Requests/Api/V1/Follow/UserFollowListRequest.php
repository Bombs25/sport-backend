<?php

namespace App\Http\Requests\Api\V1\Follow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Liste followers / following d'un utilisateur cible (mêmes query params que FollowListRequest).
 */
class UserFollowListRequest extends FormRequest
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
            'user' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', Rule::in(['followers', 'following'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'nullable', 'string', 'max:512'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user' => (int) $this->route('user'),
        ]);
    }
}

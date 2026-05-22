<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ce qu'il fait : valide `team_id` (route) et `page` (query, optionnel) pour le
 * dernier match validé et l'historique paginé des matchs.
 */
class TeamLatestMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'team_id' => $this->route('team_id'),
            'page' => $this->query('page', 1),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

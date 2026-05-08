<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ce qu'il fait : valide lecture des stats saison d'une équipe (`team_id`, `year` optionnelle).
 *
 * Pourquoi : même contrat que TeamRankingListRequest (`year`), route `teams/{team_id}/season-stats`.
 */
class TeamSeasonStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'team_id' => $this->route('team_id'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ];
    }
}

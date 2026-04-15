<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;

class TeamMatchRequestStoreRequest extends FormRequest
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
        return [
            'away_team_id' => ['required', 'integer', 'exists:teams,id'],
            'scheduled_at' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

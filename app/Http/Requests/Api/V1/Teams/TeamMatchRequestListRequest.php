<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamMatchRequestListRequest extends FormRequest
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
            'type' => ['nullable', 'string', Rule::in(['received', 'sent'])],
            'status' => ['nullable', 'string', Rule::in(['pending', 'accepted', 'refused', 'scores_to_confirm', 'finished'])],
            'scheduled_at' => ['nullable', 'date'],
            'sport_name' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

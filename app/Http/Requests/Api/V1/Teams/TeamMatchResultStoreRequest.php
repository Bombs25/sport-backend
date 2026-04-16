<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;

class TeamMatchResultStoreRequest extends FormRequest
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
            'home_score' => ['required', 'integer', 'min:0', 'max:999'],
            'away_score' => ['required', 'integer', 'min:0', 'max:999'],
            'fair_play_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'punctuality_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

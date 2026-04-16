<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamMatchResultRespondRequest extends FormRequest
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
            'decision' => ['required', 'string', Rule::in(['validate', 'refuse'])],
            'refusal_reason' => ['required_if:decision,refuse', 'nullable', 'string', 'max:5000'],
            'fair_play_rating' => ['required_if:decision,validate', 'nullable', 'integer', 'min:1', 'max:5'],
            'punctuality_rating' => ['required_if:decision,validate', 'nullable', 'integer', 'min:1', 'max:5'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

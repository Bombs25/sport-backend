<?php

namespace App\Http\Requests\Api\V1\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamNearbySearchRequest extends FormRequest
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
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sport_id' => ['sometimes', 'integer', 'exists:sports,id'],
            'competition_type' => ['sometimes', 'nullable', 'string', Rule::in(['leisure', 'competitive'])],
            'skill_level' => ['sometimes', 'nullable', 'string', Rule::in(['beginner', 'intermediate', 'expert'])],
            'radius_km' => ['sometimes', 'numeric', 'min:0.1', 'max:200'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:10000'],
        ];
    }
}

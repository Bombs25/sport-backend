<?php

namespace App\Http\Requests\Api\V1\Teams;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team && $this->user()?->can('update', $team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('teams', 'name')->ignore($team->id)],
            'sport_id' => ['sometimes', 'integer', 'exists:sports,id'],
            'description' => ['sometimes', 'nullable', 'string', 'max:200'],
            'hq_city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'hq_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'hq_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'cover_image_url' => ['sometimes', 'nullable', 'string', 'max:512'],
            'logo_url' => ['sometimes', 'nullable', 'string', 'max:512'],
            'competition_type' => ['sometimes', 'nullable', 'string', Rule::in(['leisure', 'competitive'])],
            'skill_level' => ['sometimes', 'nullable', 'string', Rule::in(['beginner', 'intermediate', 'expert'])],
        ];
    }
}

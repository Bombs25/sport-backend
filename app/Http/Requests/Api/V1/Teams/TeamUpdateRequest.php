<?php

namespace App\Http\Requests\Api\V1\Teams;

use App\Models\Team;
use App\Rules\RasterImageFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class TeamUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->resolveTeam();

        return $team !== null && $this->user()?->can('update', $team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $team = $this->resolveTeam();

        $nameRules = ['sometimes', 'string', 'max:255'];
        if ($team !== null) {
            $nameRules[] = Rule::unique('teams', 'name')->ignore($team->id);
        }

        $coverRules = $this->hasFile('cover_image_url')
            ? ['sometimes', File::types(['jpeg', 'jpg', 'png', 'gif', 'webp']), new RasterImageFile]
            : ['sometimes', 'nullable', 'string', 'max:512'];

        $logoRules = $this->hasFile('logo_url')
            ? ['sometimes', File::types(['jpeg', 'jpg', 'png', 'gif', 'webp']), new RasterImageFile]
            : ['sometimes', 'nullable', 'string', 'max:512'];

        return [
            'name' => $nameRules,
            'sport_id' => ['sometimes', 'integer', 'exists:sports,id'],
            'description' => ['sometimes', 'nullable', 'string', 'max:200'],
            'hq_city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'hq_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'hq_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'cover_image_url' => $coverRules,
            'logo_url' => $logoRules,
            'competition_type' => ['sometimes', 'nullable', 'string', Rule::in(['leisure', 'competitive'])],
            'skill_level' => ['sometimes', 'nullable', 'string', Rule::in(['beginner', 'intermediate', 'expert'])],
        ];
    }

    private function resolveTeam(): ?Team
    {
        $teamId = $this->route('team_id');
        if (! is_numeric($teamId)) {
            return null;
        }

        return Team::query()->find((int) $teamId);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('nom de l\'équipe'),
            'sport_id' => __('sport'),
            'description' => __('description'),
            'hq_city' => __('ville du QG'),
            'cover_image_url' => __('photo de couverture'),
            'logo_url' => __('logo'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __('Ce nom d\'équipe est déjà utilisé.'),
            'sport_id.exists' => __('Le sport sélectionné n\'est pas valide.'),
            'competition_type.in' => __('Le type d\'équipe sélectionné n\'est pas valide.'),
            'skill_level.in' => __('Le niveau sélectionné n\'est pas valide.'),
        ];
    }
}

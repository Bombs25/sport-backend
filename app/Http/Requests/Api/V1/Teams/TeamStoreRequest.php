<?php

namespace App\Http\Requests\Api\V1\Teams;

use App\Rules\RasterImageFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class TeamStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:teams,name'],
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'description' => ['nullable', 'string', 'max:200'],
            'hq_city' => ['required', 'string', 'max:120'],
            'hq_latitude' => ['required', 'numeric', 'between:-90,90'],
            'hq_longitude' => ['required', 'numeric', 'between:-180,180'],
            // Raster uniquement ; {@see RasterImageFile} vérifie les en-têtes binaires (clients multipart capricieux).
            'cover_image_url' => ['required', File::types(['jpeg', 'jpg', 'png', 'gif', 'webp']), new RasterImageFile],
            'logo_url' => ['required', File::types(['jpeg', 'jpg', 'png', 'gif', 'webp']), new RasterImageFile],
            'competition_type' => ['nullable', 'string', Rule::in(['leisure', 'competitive'])],
            'skill_level' => ['nullable', 'string', Rule::in(['beginner', 'intermediate', 'expert'])],
        ];
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
            'hq_latitude' => __('latitude'),
            'hq_longitude' => __('longitude'),
            'cover_image_url' => __('photo de couverture'),
            'logo_url' => __('logo'),
            'competition_type' => __('type d\'équipe'),
            'skill_level' => __('niveau'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Le nom de l\'équipe est obligatoire.'),
            'name.unique' => __('Ce nom d\'équipe est déjà utilisé.'),
            'sport_id.required' => __('Le sport est obligatoire.'),
            'sport_id.exists' => __('Le sport sélectionné n\'est pas valide.'),
            'hq_city.required' => __('La ville ou le lieu du QG est obligatoire.'),
            'hq_latitude.required' => __('Sélectionnez une ville ou un lieu dans la liste de suggestions.'),
            'hq_longitude.required' => __('Sélectionnez une ville ou un lieu dans la liste de suggestions.'),
            'cover_image_url.required' => __('La photo de couverture est obligatoire.'),
            'logo_url.required' => __('Le logo est obligatoire.'),
            'competition_type.in' => __('Le type d\'équipe sélectionné n\'est pas valide.'),
            'skill_level.in' => __('Le niveau sélectionné n\'est pas valide.'),
        ];
    }
}

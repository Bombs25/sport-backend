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
            'hq_city' => ['nullable', 'string', 'max:120'],
            'hq_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'hq_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // Raster uniquement ; {@see RasterImageFile} vérifie les en-têtes binaires (clients multipart capricieux).
            'cover_image_url' => ['required', File::types(['jpeg', 'jpg', 'png', 'gif', 'webp']), new RasterImageFile],
            'logo_url' => ['required', File::types(['jpeg', 'jpg', 'png', 'gif', 'webp']), new RasterImageFile],
            'competition_type' => ['nullable', 'string', Rule::in(['leisure', 'competitive'])],
            'skill_level' => ['nullable', 'string', Rule::in(['beginner', 'intermediate', 'expert'])],
        ];
    }
}

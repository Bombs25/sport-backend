<?php

namespace App\Http\Requests\Api\V1\Posts;

use App\Rules\RasterImageFile;
use Illuminate\Foundation\Http\FormRequest;

class PostStoreRequest extends FormRequest
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
            'body' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['sometimes', 'string', 'in:public,followers'],
            'media' => ['required', 'array', 'min:1', 'max:3'],
            'media.*' => ['file', new RasterImageFile],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'media.required' => 'Au moins une photo est requise.',
            'media.min' => 'Au moins une photo est requise.',
            'media.max' => 'Tu ne peux pas envoyer plus de 3 photos.',
            'media.*.uploaded' => 'La photo n’a pas pu être envoyée.',
            'media.*.file' => 'Chaque photo doit être un fichier image valide.',
        ];
    }
}

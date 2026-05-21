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
            'body' => ['nullable', 'string', 'max:5000', 'required_without:media'],
            'visibility' => ['sometimes', 'string', 'in:public,followers'],
            'media' => ['nullable', 'array', 'max:3', 'required_without:body'],
            'media.*' => ['file', new RasterImageFile],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'media.*.uploaded' => 'La photo n’a pas pu être envoyée.',
            'media.*.file' => 'Chaque photo doit être un fichier image valide.',
        ];
    }
}

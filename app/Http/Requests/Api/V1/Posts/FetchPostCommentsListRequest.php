<?php

namespace App\Http\Requests\Api\V1\Posts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class FetchPostCommentsListRequest extends FormRequest
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
            'publication_id' => ['required', 'integer', 'min:1', 'exists:match_results,id'],
            'publication_type' => ['required', 'string', 'in:regular,automatic'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Alias côté client : certains payloads peuvent nommer `publication_type`
        // comme `post_type` (aligné sur les endpoints commentaire existants).
        if (! $this->has('publication_type') && $this->has('post_type')) {
            $this->merge([
                'publication_type' => $this->input('post_type'),
            ]);
        }

        if (! $this->has('publication_id') && $this->has('post_id')) {
            $this->merge([
                'publication_id' => $this->input('post_id'),
            ]);
        }

        if ($this->has('publication_type') && $this->has('post_type')) {
            if ((string) $this->input('publication_type') !== (string) $this->input('post_type')) {
                throw ValidationException::withMessages([
                    'publication_type' => [__('Les paramètres publication_type et post_type doivent être identiques si les deux sont envoyés.')],
                ]);
            }
        }
    }
}

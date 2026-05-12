<?php

namespace App\Http\Requests\Api\V1\Posts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class FetchRegularPostFeedRequest extends FormRequest
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
            'viewed_post_ids' => ['sometimes', 'array', 'max:500'],
            'viewed_post_ids.*' => ['integer', 'distinct', 'exists:posts,id'],
        ];
    }

    /**
     * @throws ValidationException
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('viewed_post_ids')) {
            return;
        }

        $raw = $this->input('viewed_post_ids');

        if (is_array($raw)) {
            throw ValidationException::withMessages([
                'viewed_post_ids' => ['Le paramètre viewed_post_ids doit être une chaîne JSON, pas des clés viewed_post_ids[].'],
            ]);
        }

        if (! is_string($raw)) {
            throw ValidationException::withMessages([
                'viewed_post_ids' => ['Le paramètre viewed_post_ids doit être une chaîne JSON.'],
            ]);
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            $this->merge(['viewed_post_ids' => []]);

            return;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                'viewed_post_ids' => ['Le paramètre viewed_post_ids doit être un tableau JSON valide (ex. [12,34]).'],
            ]);
        }

        $this->merge([
            'viewed_post_ids' => array_values(array_map(
                static fn (mixed $id): int => (int) $id,
                $decoded,
            )),
        ]);
    }
}

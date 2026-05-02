<?php

namespace App\Http\Requests\Api\V1\Posts;

use Illuminate\Foundation\Http\FormRequest;

class PostMatchResultLikeToggleRequest extends FormRequest
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
            'post_id' => ['required', 'integer', 'exists:match_results,id'],
            'post_type' => ['required', 'string', 'in:regular,automatic'],
            'action' => ['required', 'string', 'in:like,dislike'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'post_id' => $this->route('post_id'),
            'post_type' => $this->input('post_type', 'regular'),
        ]);
    }
}

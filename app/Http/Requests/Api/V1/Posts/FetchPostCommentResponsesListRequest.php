<?php

namespace App\Http\Requests\Api\V1\Posts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FetchPostCommentResponsesListRequest extends FormRequest
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
            'comment_id' => [
                'required',
                'integer',
                Rule::exists('comments', 'id')->where(function ($query): void {
                    $query->where('publication_id', (int) $this->route('post_id'));
                }),
            ],
            'post_type' => ['required', 'string', 'in:regular,automatic'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'post_id' => $this->route('post_id'),
            'comment_id' => $this->route('comment_id'),
            'post_type' => $this->input('post_type', 'regular'),
        ]);
    }
}

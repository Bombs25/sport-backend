<?php

namespace App\Http\Requests\Api\V1\Posts;

use App\Http\Requests\Api\V1\Posts\Concerns\ValidatesPostPublication;
use Illuminate\Foundation\Http\FormRequest;

class FetchPostCommentResponsesListRequest extends FormRequest
{
    use ValidatesPostPublication;

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
            'post_type' => ['required', 'string', 'in:regular,automatic'],
            'post_id' => ['required', 'integer', $this->publicationExistsRule()],
            'comment_id' => [
                'required',
                'integer',
                $this->commentExistsForPublicationRule(),
            ],
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

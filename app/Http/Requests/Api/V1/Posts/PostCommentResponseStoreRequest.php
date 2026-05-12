<?php

namespace App\Http\Requests\Api\V1\Posts;

use App\Http\Requests\Api\V1\Posts\Concerns\ValidatesPostPublication;
use Illuminate\Foundation\Http\FormRequest;

class PostCommentResponseStoreRequest extends FormRequest
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
            'response' => ['required', 'string', 'max:5000'],
            'responded_to_who' => ['nullable', 'string', 'max:32', 'exists:user_profiles,handle'],
            'is_reponse_to_main_comment' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'post_id' => $this->route('post_id'),
            'comment_id' => $this->route('comment_id'),
            'post_type' => $this->input('post_type', 'regular'),
            'is_reponse_to_main_comment' => $this->boolean('is_reponse_to_main_comment', true),
        ]);
    }
}

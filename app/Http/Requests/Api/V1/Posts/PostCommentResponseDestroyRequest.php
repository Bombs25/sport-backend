<?php

namespace App\Http\Requests\Api\V1\Posts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostCommentResponseDestroyRequest extends FormRequest
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
            'response_id' => [
                'required',
                'integer',
                Rule::exists('response_commentaires', 'id')->where(function ($query): void {
                    $query->where('comment_id', (int) $this->route('comment_id'));
                }),
            ],
            'post_type' => ['required', 'string', 'in:regular,automatic'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'post_id' => $this->route('post_id'),
            'comment_id' => $this->route('comment_id'),
            'response_id' => $this->route('response_id'),
            'post_type' => $this->input('post_type', 'regular'),
        ]);
    }
}

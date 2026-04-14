<?php

namespace App\Http\Requests\Api\V1\Follow;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FollowStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([(int) $this->user()->id]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_user_id.not_in' => __('Vous ne pouvez pas vous suivre vous-même.'),
        ];
    }
}

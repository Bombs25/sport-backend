<?php

namespace App\Http\Requests\Api\V1\Follow;

use Illuminate\Foundation\Http\FormRequest;

class FollowRequestListRequest extends FormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'cursor' => ['sometimes', 'nullable', 'string', 'max:512'],
        ];
    }
}

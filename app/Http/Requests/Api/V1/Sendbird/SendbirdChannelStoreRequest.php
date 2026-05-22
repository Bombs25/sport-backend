<?php

namespace App\Http\Requests\Api\V1\Sendbird;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendbirdChannelStoreRequest extends FormRequest
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
            'target_user_id' => [
                'required',
                'integer',
                'exists:users,id',
                // On ne crée pas de canal avec soi-même.
                Rule::notIn([$this->user()?->id]),
            ],
        ];
    }
}

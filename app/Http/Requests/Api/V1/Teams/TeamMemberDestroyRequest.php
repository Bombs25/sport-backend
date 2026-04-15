<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;

class TeamMemberDestroyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'member_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return [
            'member_user_id' => $this->route('member_user_id'),
        ];
    }
}

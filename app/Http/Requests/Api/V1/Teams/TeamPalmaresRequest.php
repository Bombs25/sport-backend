<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;

class TeamPalmaresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'team_id' => $this->route('team_id'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', 'exists:teams,id'],
        ];
    }
}

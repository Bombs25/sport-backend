<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;

class TeamMatchDisputeStoreRequest extends FormRequest
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
            'dispute_reason_score_incorrect' => ['sometimes', 'boolean'],
            'dispute_reason_fair_play' => ['sometimes', 'boolean'],
            'dispute_reason_behavior' => ['sometimes', 'boolean'],
            'details' => ['required', 'string', 'max:10000'],
            'evidence' => ['nullable', 'file', 'image', 'max:102400'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dispute_reason_score_incorrect' => $this->boolean('dispute_reason_score_incorrect'),
            'dispute_reason_fair_play' => $this->boolean('dispute_reason_fair_play'),
            'dispute_reason_behavior' => $this->boolean('dispute_reason_behavior'),
        ]);
    }
}

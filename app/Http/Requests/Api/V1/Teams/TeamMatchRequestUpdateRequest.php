<?php

namespace App\Http\Requests\Api\V1\Teams;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class TeamMatchRequestUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $scheduledAt = $this->input('scheduled_at');
        if (! is_string($scheduledAt) || trim($scheduledAt) === '') {
            return;
        }

        $this->merge([
            'scheduled_at' => Carbon::parse($scheduledAt)
                ->timezone(config('app.timezone'))
                ->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'venue' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

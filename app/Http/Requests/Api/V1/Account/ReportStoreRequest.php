<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide un signalement d'utilisateur.
 *
 * Motifs alignés sur l'ActionSheet > Signaler côté app : si l'UX ajoute un
 * motif, étendre cette liste **avant** d'exposer le nouveau choix au client.
 */
class ReportStoreRequest extends FormRequest
{
    public const REASONS = [
        'inappropriate_behavior',
        'spam',
        'harassment',
        'fake_account',
        'hate_speech',
        'other',
    ];

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
            'reported_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::notIn([$this->user()->id]),
            ],
            'reason' => ['required', 'string', Rule::in(self::REASONS)],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reported_user_id.not_in' => __('Vous ne pouvez pas vous signaler vous-même.'),
        ];
    }
}

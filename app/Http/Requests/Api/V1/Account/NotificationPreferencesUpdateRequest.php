<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide un patch partiel de préférences notifications. Chaque section est
 * optionnelle ; chaque clé est un booléen. Les clés inconnues sont ignorées
 * par le service.
 */
class NotificationPreferencesUpdateRequest extends FormRequest
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
            'channels' => ['sometimes', 'array'],
            'channels.push' => ['sometimes', 'boolean'],
            'channels.email' => ['sometimes', 'boolean'],
            'channels.sms' => ['sometimes', 'boolean'],

            'social' => ['sometimes', 'array'],
            'social.mentions' => ['sometimes', 'boolean'],
            'social.likes' => ['sometimes', 'boolean'],
            'social.comments' => ['sometimes', 'boolean'],
            'social.follow' => ['sometimes', 'boolean'],

            'teams' => ['sometimes', 'array'],
            'teams.ranking' => ['sometimes', 'boolean'],
            'teams.trophies' => ['sometimes', 'boolean'],
            'teams.member_changes' => ['sometimes', 'boolean'],

            'matches' => ['sometimes', 'array'],
            'matches.requests' => ['sometimes', 'boolean'],
            'matches.reminders' => ['sometimes', 'boolean'],
            'matches.score' => ['sometimes', 'boolean'],
            'matches.end' => ['sometimes', 'boolean'],

            'messaging' => ['sometimes', 'array'],
            'messaging.direct' => ['sometimes', 'boolean'],
            'messaging.media' => ['sometimes', 'boolean'],
        ];
    }
}

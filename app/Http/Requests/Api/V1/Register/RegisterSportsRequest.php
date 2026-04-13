<?php

namespace App\Http\Requests\Api\V1\Register;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ce qu’il fait : valide le tableau `sport_ids` (au moins un id, entiers distincts, existants en base).
 *
 * Pourquoi : évite d’insérer des pivots invalides dans `user_sports` ; messages FR si le client et le référentiel divergent.
 */
class RegisterSportsRequest extends FormRequest
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
            'sport_ids' => ['required', 'array', 'min:1'],
            'sport_ids.*' => ['integer', 'distinct', 'exists:sports,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sport_ids.required' => __('Sélectionnez au moins un sport.'),
            'sport_ids.min' => __('Sélectionnez au moins un sport.'),
            'sport_ids.*.exists' => __('Un ou plusieurs sports ne sont plus disponibles. Actualisez la liste.'),
        ];
    }
}

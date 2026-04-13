<?php

namespace App\Http\Requests\Api\V1\Register;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ce qu’il fait : valide le corps de `PATCH /api/v1/auth/register/location` (coordonnées optionnelles, ville, ligne d’adresse).
 *
 * Pourquoi : étape « Où êtes-vous ? » après les coordonnées initiales ; champs optionnels ici pour permettre
 * de ne mettre à jour que la ville ou d’affiner lat/lng ; tout en gardant des bornes géographiques cohérentes.
 */
class RegisterLocationRequest extends FormRequest
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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:120'],
            'address_line' => ['nullable', 'string', 'max:255'],
        ];
    }
}

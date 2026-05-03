<?php

namespace App\Services\Register;

use App\Support\UserProfileLocation;
use Illuminate\Support\Facades\DB;

/**
 * Ce qu’il fait : met à jour ville, adresse et éventuellement lat/lng sur `user_profiles` pour l’utilisateur connecté.
 *
 * Pourquoi : étape maquette « Où êtes-vous ? » (recherche d’adresse, carte) après les coordonnées initiales
 * déjà enregistrées à l’inscription ; garde la persistance dans un service dédié (contrôleur mince).
 */
class RegisterLocationService
{
    /**
     * Persiste la localisation issue du client (React Native : lat/lng GPS ou carte, texte d’adresse).
     *
     * @param  float|null  $latitude  Degrés décimaux WGS-84 (front-end)
     * @param  float|null  $longitude  Degrés décimaux WGS-84 (front-end)
     */
    public function update(
        int $userId,
        ?float $latitude,
        ?float $longitude,
        ?string $city,
        ?string $addressLine,
    ): void {
        DB::table('user_profiles')->where('user_id', $userId)->update(array_merge(
            UserProfileLocation::columnsFromLatLng($latitude, $longitude),
            [
                'city' => $city,
                'address_line' => $addressLine,
                'updated_at' => now(),
            ],
        ));
    }
}

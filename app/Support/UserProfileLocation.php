<?php

namespace App\Support;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Ce qu’il fait : persistance **POINT SRID 4326** (`location`) avec exposition applicative en **latitude / longitude** WGS-84.
 *
 * Pourquoi : le schéma cible **MySQL / MariaDB** (spatial) ; l’API et les requêtes métier restent en lat/lng.
 */
final class UserProfileLocation
{
    /**
     * Sélection Query Builder : alias `latitude` / `longitude` dérivés de `location`.
     *
     * @return list<Expression>
     */
    public static function selectLatitudeLongitude(string $profileTable = 'user_profiles'): array
    {
        $p = $profileTable;

        return [
            DB::raw("CASE WHEN {$p}.`location` IS NULL THEN NULL ELSE ST_Latitude({$p}.`location`) END AS `latitude`"),
            DB::raw("CASE WHEN {$p}.`location` IS NULL THEN NULL ELSE ST_Longitude({$p}.`location`) END AS `longitude`"),
        ];
    }

    /**
     * Fragment `INSERT` / `UPDATE`. Si une coordonnée est absente, `location` est **null** (pas de POINT partiel).
     *
     * @return array<string, mixed>
     */
    public static function columnsFromLatLng(?float $latitude, ?float $longitude): array
    {
        if ($latitude === null || $longitude === null) {
            return ['location' => null];
        }

        $lon = (float) $longitude;
        $lat = (float) $latitude;

        return [
            'location' => DB::raw("ST_SRID(ST_GeomFromText('POINT({$lon} {$lat})'), 4326)"),
        ];
    }

    /**
     * @return array{latitude: float|null, longitude: float|null}
     */
    public static function currentLatLngForUser(int $userId): array
    {
        $row = DB::table('user_profiles')
            ->where('user_id', $userId)
            ->select([
                DB::raw('CASE WHEN `location` IS NULL THEN NULL ELSE ST_Latitude(`location`) END AS `latitude`'),
                DB::raw('CASE WHEN `location` IS NULL THEN NULL ELSE ST_Longitude(`location`) END AS `longitude`'),
            ])
            ->first();

        return [
            'latitude' => $row !== null && $row->latitude !== null ? (float) $row->latitude : null,
            'longitude' => $row !== null && $row->longitude !== null ? (float) $row->longitude : null,
        ];
    }
}

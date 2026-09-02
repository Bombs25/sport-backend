<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MapApiCotntroller extends Controller
{
    public function getPlaceInfo(float $lat,  float $lng)
    {
        $apiKey = config('services.google_maps_api_key');
        // 2. Appel à l'API Google Geocoding
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
            'latlng' => "{$lat},{$lng}",
            'key'    => $apiKey,
            'language' => 'fr' // Optionnel : pour recevoir les résultats en français
        ]);
        if ($response->failed()) {
            return response()->json(['error' => 'Erreur API Google'], 500);
        }
        $data = $response->json();
        $city = null;
        $region = null;
        // Vérifie si Google a trouvé des résultats
        if (!empty($data['results'])) {
            $components = $data['results'][0]['address_components'];

            // Parcourt les composants d'adresse pour trouver la ville et la région
            foreach ($components as $component) {
                // 'locality' correspond généralement à la ville
                if (in_array('locality', $component['types'])) {
                    $city = $component['long_name'];
                }

                // 'administrative_area_level_1' correspond à la région / état
                if (in_array('administrative_area_level_1', $component['types'])) {
                    $region = $component['long_name'];
                }
            }
        }
        // Retourne un JSON propre et minimaliste
        return $region;
    }

    public function add_region()
    {
        DB::table('teams')->where('region', "=", null)
            ->orWhere('region', "=", "")
            ->chunkById(100, function ($teams) {
                foreach ($teams as $team) {
                    DB::table('teams')
                        ->where('id', $team->id)
                        ->update(['region' => $this->getPlaceInfo($team->hq_latitude,  $team->hq_longitude)]);
                }
            });
    }
}

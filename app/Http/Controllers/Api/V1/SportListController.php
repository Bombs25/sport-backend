<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\PublicImageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Ce qu’il fait : `GET` public qui renvoie la liste des sports (id, nom, slug, type de pratique, avatar).
 *
 * Pourquoi : alimenter la grille « Quels sports pratiquez-vous ? » côté React Native ; ordre stable = ordre des `id` (insertion).
 */
class SportListController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $sports = DB::table('sports')
            ->orderBy('id')
            ->select(['id', 'name', 'slug', 'practice_type', 'avatar'])
            ->get()
            ->map(static function (object $sport): array {
                return [
                    'id' => (int) $sport->id,
                    'name' => (string) $sport->name,
                    'slug' => (string) $sport->slug,
                    'practice_type' => $sport->practice_type !== null ? (string) $sport->practice_type : null,
                    'avatar' => PublicImageUrl::from($sport->avatar),
                ];
            });

        return response()->json(['data' => $sports]);
    }
}
